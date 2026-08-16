<?php

namespace App\Traits\AdminTraits;

use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * Permission caches are versioned + TTL-bound. Any role/permission write
     * calls flushAllCachedPermissions(), which bumps the global version key and
     * atomically invalidates every admin's cached set (stale keys just expire).
     * The TTL is the safety net: a write path that skips the flush (raw SQL,
     * artisan tinker, a future seeder) self-heals within the hour instead of
     * persisting forever — a forever-cached empty set once locked the panel.
     */
    private const PERMISSION_CACHE_TTL = 3600;

    private const PERMISSION_CACHE_VERSION_KEY = 'admin_perm_cache_version';

    /**
     * Get all permissions of user.
     *
     * @return mixed
     */
    public function allPermissions(): Collection
    {
        return \Cache::remember($this->permissionCacheKey('user_permissions'), self::PERMISSION_CACHE_TTL, function () {
            return $this->roles()
                ->with('permissions:id,slug,http_method,http_path')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->merge($this->permissions)
                ->unique('id')
                ->values();
        });
    }

    public function cachedPermissions()
    {
        return cache()->remember($this->permissionCacheKey('admin_user_permissions'), 600, function () {
            return $this->roles()->with('permissions')->get()
                ->pluck('permissions')->flatten()->pluck('slug')->unique();
        });
    }

    /**
     * Check if user has permission.
     *
     * @param $ability
     * @param array $arguments
     *
     * @return bool
     */
    public function can($ability, $arguments = []): bool
    {

        // Super admin check
        if ($this->isAdministrator()) {
            return true;
        }

        $permissions = $this->cachedPermissions();

        // Allow everything if wildcard
        if ($permissions->contains('*') || empty($ability)) {
            return true;
        }

        return $permissions->contains($ability);
    }

    /**
     * Check if user has no permission.
     *
     * @param $permission
     *
     * @return bool
     */
    public function cannot(string $permission): bool
    {
        return !$this->can($permission);
    }

    /**
     * Check if user is administrator.
     *
     * @return mixed
     */
    public function isAdministrator(): bool
    {
        return $this->isRole('administrator') || $this->isRole('developer');
    }

    /**
     * Authoritative "super admin / app owner" test for security guards.
     *
     * Unlike isAdministrator() (which only knows the 'administrator'/'developer'
     * slugs), this also covers the actually-seeded top role slug 'admin' and any
     * role carrying the '*' wildcard permission. Used by the RBAC guard to decide
     * who bypasses privilege-escalation / view-only / owner-protection rules.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->isRole('admin') || $this->isRole('administrator') || $this->isRole('developer')) {
            return true;
        }

        return $this->allPermissions()->pluck('slug')->contains('*');
    }

    /**
     * Check if user is $role.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function isRole(string $role): bool
    {
        return $this->roles->pluck('slug')->contains($role);
    }

    /**
     * Check if user in $roles.
     *
     * @param array $roles
     *
     * @return mixed
     */
    public function inRoles(array $roles = []): bool
    {
        return $this->roles->pluck('slug')->intersect($roles)->isNotEmpty();
    }

    /**
     * If visible for roles.
     *
     * @param $roles
     *
     * @return bool
     */
    public function visible(array $roles = []): bool
    {
        if (empty($roles)) {
            return true;
        }

        $roles = array_column($roles, 'slug');

        return $this->inRoles($roles) || $this->isAdministrator();
    }

    /**
     * Clear cached permissions (after role/permission update).
     */
    public function forgetCachedPermissions(): void
    {
        static::forgetCachedPermissionsFor($this->id);
    }

    /**
     * Same as forgetCachedPermissions() but by id — for write points that touch
     * admin_role_users with raw DB queries and have no model instance at hand.
     */
    public static function forgetCachedPermissionsFor($adminId): void
    {
        $version = \Cache::get(self::PERMISSION_CACHE_VERSION_KEY, 1);

        \Cache::forget("admin_user_permissions_{$adminId}_v{$version}");
        \Cache::forget("user_permissions_{$adminId}_v{$version}");

        // Legacy unversioned keys (pre-versioning deploys) — drop them too so a
        // rollout mid-session can't serve a stale set.
        \Cache::forget("admin_user_permissions_{$adminId}");
        \Cache::forget("user_permissions_{$adminId}");
    }

    /**
     * Invalidate the cached permission sets of EVERY admin at once by bumping
     * the global version key (superseded keys expire via their TTL). Call this
     * from any write point that touches admin_permissions,
     * admin_role_permissions or admin_role_users — role forms, permission
     * forms, seeders. Those writes go through Eloquent sync() and raw DB
     * inserts, neither of which fires model events, so explicit calls at the
     * write points are the reliable invalidation — observers would miss them.
     */
    public static function flushAllCachedPermissions(): void
    {
        \Cache::add(self::PERMISSION_CACHE_VERSION_KEY, 1);
        \Cache::increment(self::PERMISSION_CACHE_VERSION_KEY);
    }

    private function permissionCacheKey(string $prefix): string
    {
        $version = \Cache::get(self::PERMISSION_CACHE_VERSION_KEY, 1);

        return "{$prefix}_{$this->id}_v{$version}";
    }

    /**
     * Detach models from the relationship.
     *
     * @return void
     */
    protected static function bootHasPermissions()
    {
        static::saved(function ($model) {
            $model->forgetCachedPermissions();
        });

        static::deleting(function ($model) {
            $model->roles()->detach();
            $model->permissions()->detach();
            $model->forgetCachedPermissions();
        });
    }
}
