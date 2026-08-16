<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sidebar entry for the "Games Settings" (إعدادات الألعاب) page. The panel menu is
 * DB-driven (admin_menu); the new item sits under the same Games parent as the
 * existing "game-settings" row (same parent_id, order + 1) so it lands in the
 * same الألعاب section. The custom RTL renderer (vendor/admin/partials/menu.blade.php)
 * hides leaf items with an empty permission when check_menu_roles=true, so we copy
 * the sibling's permission (and clone its admin_role_menu bindings) to keep the
 * item visible to the same roles.
 */
return new class extends Migration
{
    private const URI = 'games-access-settings';
    private const SIBLING_URI = 'all-games';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        if (DB::table('admin_menu')->where('uri', self::URI)->exists()) {
            return;
        }

        $sibling = DB::table('admin_menu')->where('uri', self::SIBLING_URI)->first();

        $menuId = DB::table('admin_menu')->insertGetId([
            'parent_id'  => $sibling->parent_id ?? 0,
            'order'      => ($sibling->order ?? 0) + 1,
            'title'      => 'Games Settings',
            'icon'       => 'fa-gamepad',
            'uri'        => self::URI,
            'permission' => $sibling->permission ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($sibling && Schema::hasTable('admin_role_menu')) {
            $bindings = DB::table('admin_role_menu')
                ->where('menu_id', $sibling->id)
                ->pluck('role_id');

            foreach ($bindings as $roleId) {
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
