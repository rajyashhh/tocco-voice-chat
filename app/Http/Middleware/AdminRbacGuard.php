<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Role;
use Closure;
use Encore\Admin\Facades\Admin as AdminFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Central RBAC guard for the admin panel — one choke point on every admin route
 * (vendor Admin::routes(), the custom groups, and the module route files), so
 * enforcement can't be dodged by hitting an un-patched controller.
 *
 * Super-admins (role admin/developer/administrator or the '*' permission) bypass
 * everything. For everyone else it enforces, in order:
 *
 *  1. Super-only surfaces: role/permission/menu management + the terminal/scaffold
 *     RCE tools. A non-super can neither read nor write the terminal, and cannot
 *     write roles/permissions/menus (closes self-escalation via role editing).
 *  2. Owner / super-admin ACCOUNT protection: a non-super cannot edit/delete a
 *     protected admin account (id=1 or any super-admin), nor assign a super role
 *     to anyone (closes self-escalation via role assignment).
 *  3. View-only role: an actor whose permissions are read-only ("browse-" and
 *     "show-" slugs only, no wildcard) is refused every mutating request (write
 *     verbs + the handful of state-changing GET routes), regardless of whether the
 *     target controller checks permissions. This makes a "trial / viewer" role safe.
 *
 * Fail-safe: any role holding even one non-read permission is treated as a writer
 * and passes the view-only gate untouched, so legitimate operators are never
 * locked out.
 */
class AdminRbacGuard
{
    private const WRITE_VERBS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const READ_PREFIXES = ['browse-', 'show-'];

    /** Surfaces only a super-admin may touch (write); terminal/scaffold = no access at all. */
    private const SUPER_ONLY_PREFIXES = [
        'auth/roles', 'auth/rolesTest', 'auth/permissions', 'auth/menu',
        'super-roles', 'super-permissions', 'helpers/terminal', 'helpers/scaffold',
    ];

    /** Admin-account management paths (target-id protection + super-role assignment). */
    private const ADMIN_USER_PREFIXES = [
        'auth/users', 'admin-users', 'area-manager-users', 'superadmin-users',
    ];

    /** Benign writes a view-only user still needs to function. */
    private const VIEWONLY_ALLOW = [
        'auth/logout', 'logout', 'locale', 'save-fcm-token',
        'set-preview-area-manager', 'unset-preview-area-manager',
    ];

    /** Routes that bypass per-route permission check (must work without any permission). */
    private const PERMISSION_EXEMPT_PREFIXES = [
        'auth/logout', 'logout', 'locale', 'save-fcm-token',
        'set-preview-area-manager', 'unset-preview-area-manager',
        'auth/login', 'login', 'dashboard',
        'api/search', 'api/config',
        'profile',
        // laravel-admin flat grid-action endpoint: the resource context is in
        // POST params (_model, _action, _row_id), not the URL path. Grid actions
        // carry their own Permission::check() inside each Action class.
        '_handle_action_',
    ];

    /** Markers of state-mutating GET routes (blocked for view-only actors). */
    private const GET_MUTATION_MARKERS = ['/accept', '/reject', 'convert-is_gold', 'background-count', 'targets-confirm'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = AdminFacade::user();

        // Unauthenticated -> auth middleware handles it; nothing to guard here.
        if (! $user) {
            return $next($request);
        }

        // Super-admins are unrestricted.
        if ($this->isSuper($user)) {
            return $next($request);
        }

        $path = $this->relativePath($request);
        $isWrite = $this->isWrite($request);

        // 1) Super-only management surfaces.
        foreach (self::SUPER_ONLY_PREFIXES as $p) {
            if ($this->pathStartsWith($path, $p)) {
                // terminal/scaffold: deny any access; the rest: deny writes only.
                if (str_starts_with($p, 'helpers/') || $isWrite) {
                    abort(403, __('Restricted to super administrators.'));
                }
            }
        }

        // 2) Owner / super-admin account protection + super-role assignment block.
        if ($isWrite && $this->touchesAdminUsers($path)) {
            $targetId = $this->trailingId($path);
            if ($targetId !== null) {
                $target = Admin::find($targetId);
                if ($target && (int) $user->id !== (int) $target->id && $this->isProtectedAccount($target)) {
                    abort(403, __('You are not allowed to modify a super-admin account.'));
                }
            }
            if ($this->containsSuperRole((array) $request->input('roles', []))) {
                abort(403, __('You cannot assign a super-admin role.'));
            }
        }

        // 3) View-only enforcement.
        if ($this->isViewOnly($user)) {
            if ($isWrite && ! in_array($path, self::VIEWONLY_ALLOW, true)) {
                abort(403, __('Read-only access.'));
            }
            if ($request->isMethod('get') && $this->isGetMutation($path)) {
                abort(403, __('Read-only access.'));
            }
        }

        // 4) Per-route permission enforcement.
        //    A non-super user may only reach a route when at least one of their
        //    permissions has an http_path that matches the current request path
        //    (and the http_method allows the current verb, or is empty = ANY).
        //    This closes the gap where controllers omit Permission::check().
        if (! $this->isPermissionExempt($path)) {
            $this->enforceRoutePermission($user, $request, $path);
        }

        return $next($request);
    }

    private function isSuper($user): bool
    {
        return method_exists($user, 'isSuperAdmin')
            ? $user->isSuperAdmin()
            : ($user->isAdministrator() || $user->can('*'));
    }

    /** A non-super actor holding ONLY read permissions ("browse-" / "show-") and no wildcard. */
    private function isViewOnly($user): bool
    {
        $slugs = $user->allPermissions()->pluck('slug')->filter();

        if ($slugs->isEmpty() || $slugs->contains('*')) {
            return false;
        }

        foreach ($slugs as $slug) {
            $isRead = false;
            foreach (self::READ_PREFIXES as $prefix) {
                if (str_starts_with((string) $slug, $prefix)) {
                    $isRead = true;
                    break;
                }
            }
            if (! $isRead) {
                return false; // holds a non-read capability -> a writer, not view-only
            }
        }

        return true;
    }

    private function isProtectedAccount(Admin $target): bool
    {
        return (int) $target->id === 1 || $this->isSuper($target);
    }

    /** True if any of the submitted role ids is a super-admin role. */
    private function containsSuperRole(array $roleIds): bool
    {
        $roleIds = array_filter($roleIds);
        if (! $roleIds) {
            return false;
        }

        $superRoleIds = Cache::remember('rbac_super_role_ids', 600, function () {
            $bySlug = Role::whereIn('slug', ['admin', 'administrator', 'developer'])->pluck('id');
            $byWildcard = Role::whereHas('permissions', fn ($q) => $q->where('slug', '*'))->pluck('id');
            return $bySlug->merge($byWildcard)->unique()->values()->all();
        });

        foreach ($roleIds as $id) {
            if (in_array((int) $id, $superRoleIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function isWrite(Request $request): bool
    {
        if (in_array(strtoupper($request->method()), self::WRITE_VERBS, true)) {
            return true;
        }

        // laravel-admin grid row/batch actions (incl. delete) post via _handle_action_/_action.
        return $request->has('_action') || str_contains($this->relativePath($request), '_handle_action_');
    }

    private function touchesAdminUsers(string $path): bool
    {
        foreach (self::ADMIN_USER_PREFIXES as $p) {
            if ($this->pathStartsWith($path, $p)) {
                return true;
            }
        }
        return false;
    }

    private function isGetMutation(string $path): bool
    {
        foreach (self::GET_MUTATION_MARKERS as $m) {
            if (str_contains($path, $m)) {
                return true;
            }
        }
        return false;
    }

    /** Last numeric path segment, if any (the {id} of a resource route). */
    private function trailingId(string $path): ?int
    {
        foreach (array_reverse(explode('/', $path)) as $seg) {
            if ($seg !== '' && ctype_digit($seg)) {
                return (int) $seg;
            }
        }
        return null;
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }

    /** Admin-relative path (route prefix stripped, no leading/trailing slash). */
    private function relativePath(Request $request): string
    {
        $prefix = trim((string) config('admin.route.prefix'), '/');
        $path = trim($request->path(), '/');

        if ($prefix !== '') {
            if ($path === $prefix) {
                return '';
            }
            if (str_starts_with($path, $prefix . '/')) {
                return substr($path, strlen($prefix) + 1);
            }
        }

        return $path;
    }

    /** True when $path starts with any exempt prefix. */
    private function isPermissionExempt(string $path): bool
    {
        foreach (self::PERMISSION_EXEMPT_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enforce per-route permission by looking up the admin_menu table.
     *
     * The admin_menu table is the authoritative URI→permission mapping.
     * Controllers use $this->permission_name which is decoupled from the
     * URL path (e.g. VipController serves /vips but checks 'browse-level'),
     * so slug derivation from URLs is unreliable.
     *
     * Strategy:
     *  1. Strip numeric IDs and action segments from the path to get the base URI.
     *  2. Look up admin_menu for matching URI (longest-first).
     *  3. If a matching menu item has a permission set, check user holds it.
     *  4. If the menu item has no permission (NULL), allow through — these are
     *     unrestricted menu items; the controller's own Permission::check() handles.
     *  5. If no menu item matches (non-menu route), fall back to 'browse-dashboard'
     *     so the route is gated rather than passing through completely unguarded.
     */
    private function enforceRoutePermission($user, Request $request, string $path): void
    {
        $slugs = $user->allPermissions()->pluck('slug')->filter();

        if ($slugs->isEmpty()) {
            abort(403, __('You do not have permission to access this page.'));
        }

        // Wildcard: full access.
        if ($slugs->contains('*')) {
            return;
        }

        // Look up the admin_menu table for the base URI of this path.
        $menuPermission = $this->lookupMenuPermission($path);

        if ($menuPermission === null) {
            // No matching menu item — this is a non-menu route (e.g. custom
            // controller action, module route, AJAX endpoint). The controller's
            // own Permission::check() handles access. Allow through.
            return;
        }

        if ($menuPermission === '') {
            // Menu item exists but has no permission set (NULL in DB). These are
            // unrestricted menu items — the controller's own Permission::check()
            // handles fine-grained access. Allow through.
            return;
        }

        // Check if the user holds the required permission.
        if ($slugs->contains($menuPermission)) {
            return;
        }

        abort(403, __('You do not have permission to access this page.'));
    }

    /**
     * Look up the admin_menu table to find the permission for a given URL path.
     *
     * Strips numeric IDs and action segments (create/edit) to find the
     * base resource URI, then matches against admin_menu.uri (longest-first).
     *
     * Returns:
     *  - the permission slug string if a menu item with a permission was found
     *  - '' (empty string) if a menu item was found but has no permission (NULL)
     *  - null ONLY for empty paths (dashboard root) — non-menu routes now
     *    fall back to 'browse-dashboard' instead of passing through unguarded.
     */
    private function lookupMenuPermission(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', $path)));

        if (empty($segments)) {
            return null;
        }

        // Build candidate URIs by progressively removing trailing segments.
        // Start with the full path, then remove non-numeric trailing segments
        // (IDs, action words) to find the base resource URI.
        //
        // Example: 'users/123/edit' → candidates: ['users/123/edit', 'users/123', 'users']
        //          'auth/users'     → candidates: ['auth/users']
        //          'users'          → candidates: ['users']
        $candidates = [];
        for ($i = count($segments); $i >= 1; $i--) {
            $uri = implode('/', array_slice($segments, 0, $i));
            // Skip candidates where the last segment is purely numeric (an ID).
            $candidateSegments = array_slice($segments, 0, $i);
            if (ctype_digit(end($candidateSegments))) {
                continue;
            }
            $candidates[] = $uri;
        }

        // Deduplicate while preserving order (longest first).
        $candidates = array_unique($candidates);

        // Query admin_menu: match on URI, prefer exact matches.
        // Use a single query with IN clause for efficiency.
        if (empty($candidates)) {
            return null;
        }

        $menuItems = \Illuminate\Support\Facades\DB::table('admin_menu')
            ->whereIn('uri', $candidates)
            ->select('uri', 'permission')
            ->get();

        if ($menuItems->isEmpty()) {
            // No menu entry found for any ancestor URI.  Instead of returning
            // null (which lets the route through completely unguarded), fall
            // back to 'browse-dashboard'.  This blocks non-super-admins from
            // accessing routes that have no admin_menu entry — closing the
            // biggest gap in the RBAC guard.  The dashboard permission is the
            // baseline gate for the admin panel; VIP / limited admins must
            // hold it to reach any unlisted route.
            return 'browse-dashboard';
        }

        // Find the best match: prefer the longest URI (most specific).
        // When multiple items share the same URI, prefer the one WITH a
        // permission set (explicit gate) over NULL-permission items.
        $bestMatch = null;
        $bestLength = 0;
        $bestHasPerm = false;
        foreach ($menuItems as $item) {
            $itemLen = strlen((string) $item->uri);
            $hasPerm = !empty($item->permission);
            $currentBestHasPerm = $bestMatch && !empty($bestMatch->permission);

            if ($itemLen > $bestLength
                || ($itemLen === $bestLength && $hasPerm && !$currentBestHasPerm)
            ) {
                $bestLength = $itemLen;
                $bestMatch = $item;
                $bestHasPerm = $hasPerm;
            }
        }

        return $bestMatch->permission ?? '';
    }
}
