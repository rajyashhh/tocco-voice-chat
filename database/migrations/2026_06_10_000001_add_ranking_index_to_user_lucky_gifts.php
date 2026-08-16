<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering index for the lucky-gift ranking aggregation (class=4 on /api/ranking):
 *
 *   SELECT SUM(total_win) as exp, user_id
 *   FROM user_lucky_gifts
 *   WHERE created_at BETWEEN ? AND ?
 *   GROUP BY user_id ORDER BY exp DESC
 *
 * Production EXPLAIN showed a full index scan + Using temporary + Using filesort:
 * the table (77K+ rows, ~10 inserts/s during lucky bursts) had NO index on
 * created_at at all (only PRIMARY, user_id, (user_id, gift_id)). With
 * (created_at, user_id, total_win) the period filter becomes a range scan and the
 * index covers the aggregation, so no row lookups are needed.
 *
 * Idempotent: checks for an existing index with the same leading columns before
 * creating, so it is safe to run multiple times.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_ulg_created_user_win';

    public function up(): void
    {
        if ($this->compositeIndexExists('user_lucky_gifts', ['created_at', 'user_id', 'total_win'])) {
            return;
        }

        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->index(['created_at', 'user_id', 'total_win'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
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
