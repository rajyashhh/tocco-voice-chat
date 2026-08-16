<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collapse the duplicated games min-level config into a single source of truth.
 *
 * Historically two config keys held the same value, kept in sync by hidden JS on
 * the Games Access Settings page:
 *   - games_min_level   (current, shown in the panel)
 *   - min_level_to_play (legacy)
 *
 * games_min_level becomes the only key. If games_min_level is unset/0 but the
 * legacy key carried a real value, that value is migrated forward so behaviour is
 * preserved. The legacy row is then removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('configs')) {
            return;
        }

        $legacy = DB::table('configs')->where('name', 'min_level_to_play')->value('value');
        $current = DB::table('configs')->where('name', 'games_min_level')->value('value');

        // Preserve behaviour: if the panel key is empty/0 but the legacy key held a
        // real threshold, carry it forward into the surviving key.
        if ((int) $current === 0 && (int) $legacy > 0) {
            DB::table('configs')->updateOrInsert(
                ['name' => 'games_min_level'],
                ['value' => (int) $legacy]
            );
        }

        DB::table('configs')->where('name', 'min_level_to_play')->delete();
    }

    public function down(): void
    {
        // One-way data consolidation; the legacy key is intentionally not recreated.
    }
};
