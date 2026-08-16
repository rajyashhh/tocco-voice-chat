<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sidebar entry for the Third Party services page (الطرف الثالث →
 * /admin/third-party-settings).
 *
 * The Third Party services (Realtime/Centrifugo, Firebase FCM, Phone Auth,
 * Firebase App Config, Storage/GCS, YouTube, UTD Stream, Games) used to live as
 * one tab inside the main Settings page. They now have their own page and route
 * (admin.settings.third-party), each service on its own inner tab. This adds the
 * DB-driven menu row so every install gets the link out of the box.
 *
 * Visibility is anchored to the existing Settings row: we copy its permission
 * and clone its admin_role_menu bindings, so exactly the roles that can see
 * Settings can see Third Party (the custom RTL menu renderer hides leaf items
 * with an empty permission when check_menu_roles=true).
 *
 * Idempotent + fail-safe: guarded by a uri existence check, and if the Settings
 * anchor is somehow absent it falls back to any row carrying a permission rather
 * than seeding an orphan/invisible node.
 */
return new class extends Migration
{
    private const URI = 'third-party-settings';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        if (DB::table('admin_menu')->where('uri', self::URI)->exists()) {
            return;
        }

        // Anchor to the Settings row so Third Party sits right beside it and
        // inherits identical parent group, permission, and role visibility.
        // Fall back to any row so we never seed an orphan.
        $anchor = DB::table('admin_menu')->where('uri', 'settings')->first()
            ?? DB::table('admin_menu')->orderByDesc('order')->first();

        // Nest under the same parent group as Settings (it is a child of the
        // top-level "Settings" group, not a root item), placed just after it.
        $parentId = $anchor->parent_id ?? 0;
        $order    = $anchor ? (int) $anchor->order + 1 : (int) DB::table('admin_menu')->max('order') + 1;

        $menuId = DB::table('admin_menu')->insertGetId([
            'parent_id'  => $parentId,
            'order'      => $order,
            'title'      => 'Third Party',
            'icon'       => 'fa-satellite-dish',
            'uri'        => self::URI,
            'permission' => $anchor->permission ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($anchor && Schema::hasTable('admin_role_menu')) {
            $roleIds = DB::table('admin_role_menu')
                ->where('menu_id', $anchor->id)
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
