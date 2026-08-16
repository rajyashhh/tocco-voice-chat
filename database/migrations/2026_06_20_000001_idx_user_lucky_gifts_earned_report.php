<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering index for the Admin "App Earned" report grid
 * (App\Admin\Controllers\AppEarnedReportController):
 *
 *   SELECT MIN(created_at), SUM(number), gift_id, user_id, gift_price, ...
 *   FROM user_lucky_gifts
 *   WHERE created_at >= ?           -- default 7-day window applied in the grid
 *   GROUP BY gift_id, user_id, gift_price
 *
 * The pre-existing index (2026_06_10) is (created_at, user_id, total_win), which
 * serves the /api/ranking aggregation but does NOT cover this report's GROUP BY
 * on (user_id, gift_id, gift_price). Without a matching index the grid does a
 * full scan + Using temporary + filesort over the whole table.
 *
 * (user_id, gift_id, gift_price, created_at) covers the grouping keys and keeps
 * created_at as a trailing column for the bounded date window.
 *
 * Additive only, idempotent (skips if a same-leading-column index already
 * exists), and reversible.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_ulg_user_gift_price_created';
    private const COLUMNS = ['user_id', 'gift_id', 'gift_price', 'created_at'];

    public function up(): void
    {
        if (! Schema::hasTable('user_lucky_gifts')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('user_lucky_gifts', $column)) {
                return;
            }
        }

        if ($this->compositeIndexExists('user_lucky_gifts', self::COLUMNS)) {
            return;
        }

        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->index(self::COLUMNS, self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_lucky_gifts')) {
            return;
        }

        if ($this->indexNameExists('user_lucky_gifts', self::INDEX_NAME)) {
            Schema::table('user_lucky_gifts', function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }

    /**
     * True if any existing index starts with exactly the given leading columns.
     */
    private function compositeIndexExists(string $table, array $leadingColumns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            $columns = array_values($index['columns'] ?? []);
            if (array_slice($columns, 0, count($leadingColumns)) === $leadingColumns) {
                return true;
            }
        }

        return false;
    }

    private function indexNameExists(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
