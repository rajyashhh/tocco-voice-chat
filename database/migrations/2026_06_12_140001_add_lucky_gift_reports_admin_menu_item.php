<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sidebar entry for the lucky-gifts reports page. The panel menu is DB-driven
 * (admin_menu); the new item sits right next to "Lucky Gift Setting" (same
 * parent, order + 1) so it lands in the same sidebar section.
 */
return new class extends Migration
{
    private const URI = 'lucky-gift-reports';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        if (DB::table('admin_menu')->where('uri', self::URI)->exists()) {
            return;
        }

        $settingsItem = DB::table('admin_menu')->where('uri', 'lucky-gift-settings')->first();

        DB::table('admin_menu')->insert([
            'parent_id' => $settingsItem->parent_id ?? 0,
            'order' => ($settingsItem->order ?? 0) + 1,
            'title' => 'Lucky Gifts Reports',
            'icon' => 'fa-line-chart',
            'uri' => self::URI,
            'permission' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        DB::table('admin_menu')->where('uri', self::URI)->delete();
    }
};
