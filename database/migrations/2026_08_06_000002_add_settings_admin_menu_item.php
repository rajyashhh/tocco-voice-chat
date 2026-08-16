<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sidebar entry for the main Settings page (الإعدادات → /admin/settings).
 *
 * The panel menu is DB-driven (admin_menu). The Settings page, its route
 * (admin.settings) and every field it holds — UTD Stream credentials, Firebase,
 * Storage/storage_url, feature toggles — all exist and work when the URL is
 * opened directly, but there was NO admin_menu row pointing at it, so on a clean
 * install the operator simply cannot find it. This adds the row from the root so
 * every new client gets the Settings link out of the box via `php artisan migrate`.
 *
 * The custom RTL renderer (admin/views/partials/menu.blade.php) hides leaf items
 * with an empty permission when check_menu_roles=true, so we copy a top-level
 * sibling's permission (and clone its admin_role_menu bindings) to keep the item
 * visible to the same roles. Idempotent: guarded by a uri existence check.
 */
return new class extends Migration
{
    private const URI = 'settings';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        if (DB::table('admin_menu')->where('uri', self::URI)->exists()) {
            return;
        }

        // Anchor next to an existing top-level functional item so it inherits a
        // sensible permission/role visibility. "Configurations" (uri=configs) is
        // the closest top-level admin-config sibling; fall back to any row that
        // carries a non-empty permission if it is absent.
        $sibling = DB::table('admin_menu')->where('uri', 'configs')->first()
            ?? DB::table('admin_menu')->whereNotNull('permission')->where('permission', '!=', '')->first();

        $maxOrder = (int) DB::table('admin_menu')->max('order');

        $menuId = DB::table('admin_menu')->insertGetId([
            'parent_id'  => 0,
            'order'      => $maxOrder + 1,
            'title'      => 'Settings',
            'icon'       => 'fa-cogs',
            'uri'        => self::URI,
            'permission' => $sibling->permission ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($sibling && Schema::hasTable('admin_role_menu')) {
            $roleIds = DB::table('admin_role_menu')
                ->where('menu_id', $sibling->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $exists = DB::table('admin_role_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->exists();

                if (!$exists) {
                    DB::table('admin_role_menu')->insert([
                        'role_id'    => $roleId,
                        'menu_id'    => $menuId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        $menu = DB::table('admin_menu')->where('uri', self::URI)->first();

        if (!$menu) {
            return;
        }

        if (Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->where('menu_id', $menu->id)->delete();
        }

        DB::table('admin_menu')->where('uri', self::URI)->delete();
    }
};
