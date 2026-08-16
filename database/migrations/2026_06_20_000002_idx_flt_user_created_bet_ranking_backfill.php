<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lucky ranking backfill (RankingRepository::luckyScoresQuery) runs
 *   SELECT user_id, SUM(bet_amount) FROM fair_luck_transactions
 *   WHERE created_at BETWEEN ? AND ? AND user_id <> 0 GROUP BY user_id
 * The monthly base build scans millions of rows. idx_flt_user_created
 * (user_id, created_at) lets MySQL group by user_id without a filesort but
 * still does a row lookup per row to read bet_amount -> statement timeout
 * (observed every 15 min on jo prod: "Query execution was interrupted,
 * maximum statement execution time exceeded").
 *
 * Extending it to (user_id, created_at, bet_amount) makes the aggregate
 * INDEX-ONLY: group by user_id (leading), range-filter created_at, sum
 * bet_amount straight from the index — no filesort, no row lookups. The new
 * index is a superset of idx_flt_user_created, so the old one is dropped to
 * avoid duplicate write cost on this high-insert table.
 */
return new class extends Migration
{
    private const TABLE   = 'fair_luck_transactions';
    private const NEW_IDX = 'idx_flt_user_created_bet';
    private const OLD_IDX  = 'idx_flt_user_created';

    private function hasIndex(string $table, string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index])) > 0;
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }
        if (! $this->hasIndex(self::TABLE, self::NEW_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['user_id', 'created_at', 'bet_amount'], self::NEW_IDX);
            });
        }
        if ($this->hasIndex(self::TABLE, self::OLD_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::OLD_IDX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }
        if (! $this->hasIndex(self::TABLE, self::OLD_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], self::OLD_IDX);
            });
        }
        if ($this->hasIndex(self::TABLE, self::NEW_IDX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::NEW_IDX);
            });
        }
    }
};
