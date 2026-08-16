<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner 2026-08-14: the two features pages were merged — the settings
 * switches of the old `app-feature` page (FeatureAppController) now live on
 * the unified `app-features` cards page, and the old route is a redirect.
 *
 * This migration:
 *  1. Removes any sidebar row still pointing at uri='app-feature' (plus its
 *     admin_role_menu bindings) so the sidebar shows one features entry.
 *  2. Normalizes the surviving app-features row title to 'App Features' —
 *     the canonical key the menu renderer translates via lang JSON
 *     ("App Features" → "ميزات التطبيق").
 *
 * Rules: existence-checked before every mutation, no unconditional inserts,
 * idempotent (re-run finds nothing to change), down() restores the removed
 * row next to its sibling and reverts the title.
 */
return new class extends Migration
{
    private const OLD_URI = 'app-feature';
    private const NEW_URI = 'app-features';
    private const NEW_TITLE = 'App Features';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        // 1. Drop the legacy app-feature row(s) + role bindings.
        $oldIds = DB::table('admin_menu')->where('uri', self::OLD_URI)->pluck('id')->all();
        foreach ($oldIds as $id) {
            if (Schema::hasTable('admin_role_menu')) {
                DB::table('admin_role_menu')->where('menu_id', $id)->delete();
            }
            DB::table('admin_menu')->where('id', $id)->delete();
        }

        // 2. Normalize the unified row's title, only when it exists and differs.
        DB::table('admin_menu')
            ->where('uri', self::NEW_URI)
            ->where('title', '!=', self::NEW_TITLE)
            ->update(['title' => self::NEW_TITLE, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        // Restore the legacy row only if it is absent, anchored beside the
        // unified row so it reappears in the same section with the same
        // permission/role visibility (mirrors the 08-13 menu migrations).
        if (!DB::table('admin_menu')->where('uri', self::OLD_URI)->exists()) {
            $anchor = DB::table('admin_menu')->where('uri', self::NEW_URI)->first();

            if ($anchor) {
                $menuId = DB::table('admin_menu')->insertGetId([
                    'parent_id'  => $anchor->parent_id,
                    'order'      => (int) $anchor->order + 1,
                    'title'      => 'App Feature',
                    'icon'       => 'fa-toggle-on',
                    'uri'        => self::OLD_URI,
                    'permission' => $anchor->permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('admin_role_menu')) {
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
        }
    }
};