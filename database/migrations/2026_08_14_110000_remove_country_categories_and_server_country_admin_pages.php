<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner 2026-08-14: the «Country Categories» (country-categories) and
 * «Server Countries» (server-country) admin pages are removed for good —
 * both tables are empty on production (country_categories = 0 rows,
 * servers = 0 rows) and the whole structure is a leftover of the retired
 * multi-server lineage. Their controllers/actions/routes are deleted from
 * the source in the same change; this migration cleans the DB-driven admin
 * surfaces behind them:
 *
 *   1. admin_menu rows (uri = 'country-categories' / 'server-country')
 *      together with their admin_role_menu bindings.
 *   2. admin_permissions rows whose slug targets those two pages
 *      ({method}-country-categories / {method}-server-country) together
 *      with their permission_types and admin_role_permissions bindings.
 *
 * Every delete is guarded by an existence check. down() restores the menu
 * rows (anchored beside 'countries' in the Settings group, mirroring
 * 2026_08_13_130000_add_third_party_settings_menu) and re-seeds the default
 * CRUD permission slugs.
 *
 * NOTE: countries.country_category_id is intentionally NOT dropped here —
 * see database/migrations/_deferred/ for the prepared (not yet scheduled)
 * column-drop migration.
 */
return new class extends Migration
{
    private const URIS = ['country-categories', 'server-country'];

    /** Default CRUD methods used by the panel's permission slugs. */
    private const METHODS = ['browse', 'create', 'delete', 'edit', 'show'];

    public function up(): void
    {
        if (Schema::hasTable('admin_menu')) {
            foreach (self::URIS as $uri) {
                $menuIds = DB::table('admin_menu')->where('uri', $uri)->pluck('id')->all();
                if (!$menuIds) {
                    continue;
                }
                if (Schema::hasTable('admin_role_menu')) {
                    DB::table('admin_role_menu')->whereIn('menu_id', $menuIds)->delete();
                }
                DB::table('admin_menu')->whereIn('id', $menuIds)->delete();
            }
        }

        if (Schema::hasTable('admin_permissions')) {
            $slugs = [];
            foreach (self::URIS as $key) {
                foreach (self::METHODS as $method) {
                    $slugs[] = "{$method}-{$key}";
                }
                // Toggle-style extras follow the same {action}-{key} pattern.
                $slugs[] = "status-switch-{$key}";
                $slugs[] = "move-switch-{$key}";
            }

            $permissionIds = DB::table('admin_permissions')
                ->whereIn('slug', $slugs)
                ->pluck('id')
                ->all();

            if ($permissionIds) {
                if (Schema::hasTable('permission_types')) {
                    DB::table('permission_types')->whereIn('permission_id', $permissionIds)->delete();
                }
                if (Schema::hasTable('admin_role_permissions')) {
                    DB::table('admin_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
                }
                DB::table('admin_permissions')->whereIn('id', $permissionIds)->delete();

                // Per-admin permission caches are versioned (see
                // reference-admin-permissions-cache-incident) — bump them so no
                // admin keeps a stale set that still references the dead pages.
                if (class_exists(\App\Models\Admin::class)
                    && method_exists(\App\Models\Admin::class, 'flushAllCachedPermissions')) {
                    \App\Models\Admin::flushAllCachedPermissions();
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_menu')) {
            // Anchor beside the Countries row so the restored links land back in
            // the Settings group; fall back to root if it is somehow absent.
            $anchor = DB::table('admin_menu')->where('uri', 'countries')->first();

            $titles = [
                'country-categories' => 'Country Categories',
                'server-country'     => 'Server Countries',
            ];

            foreach ($titles as $uri => $title) {
                if (DB::table('admin_menu')->where('uri', $uri)->exists()) {
                    continue;
                }
                DB::table('admin_menu')->insert([
                    'parent_id'  => $anchor->parent_id ?? 0,
                    'order'      => $anchor ? (int) $anchor->order + 1 : (int) DB::table('admin_menu')->max('order') + 1,
                    'title'      => $title,
                    'icon'       => 'fa-globe',
                    'uri'        => $uri,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('admin_permissions')) {
            foreach (self::URIS as $key) {
                foreach (self::METHODS as $method) {
                    DB::table('admin_permissions')->updateOrInsert(
                        ['slug' => "{$method}-{$key}"],
                        [
                            'name'       => ucfirst($method) . ' ' . str_replace('-', ' ', $key),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            if (class_exists(\App\Models\Admin::class)
                && method_exists(\App\Models\Admin::class, 'flushAllCachedPermissions')) {
                \App\Models\Admin::flushAllCachedPermissions();
            }
        }
    }
};
