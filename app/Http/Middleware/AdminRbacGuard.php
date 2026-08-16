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
}
