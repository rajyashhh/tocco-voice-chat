<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the sidebar entry "Third Party" -> "Third Party Settings" so the
 * menu renderer (admin_trans) picks the ar.json translation
 * «إعدادات الطرف الثالث» instead of «طرف ثالث».
 *
 * Idempotent: scoped by uri, the update is a no-op when already renamed.
 */
return new class extends Migration
{
    private const URI = 'third-party-settings';

    public function up(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        DB::table('admin_menu')
            ->where('uri', self::URI)
            ->update(['title' => 'Third Party Settings', 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        DB::table('admin_menu')
            ->where('uri', self::URI)
            ->update(['title' => 'Third Party', 'updated_at' => now()]);
    }
};
