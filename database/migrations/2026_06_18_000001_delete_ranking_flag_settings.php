<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ranking cutover complete: the unified Redis leaderboard (RankingScoreService)
 * is now the ONLY ranking path. Both feature flags are removed from code, so the
 * settings rows that gated them are dead — delete them.
 *
 *   ranking_redis_read   gated the read path (always Redis now).
 *   ranking_direct_write gated the lucky-gift write path (always direct now).
 *
 * Forward-only: the original seed migration (2026_06_13_000007) is left in place
 * as run history and is never edited. Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')
            ->whereIn('key', ['ranking_redis_read', 'ranking_direct_write'])
            ->delete();
    }

    public function down(): void
    {
        // No-op: the flags no longer exist in code, so re-seeding them would have
        // no effect. Restoring them is not meaningful after the cutover.
    }
};
