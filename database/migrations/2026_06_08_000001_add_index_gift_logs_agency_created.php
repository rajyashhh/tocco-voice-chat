<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a composite (agency_id, created_at) index on gift_logs.
 *
 * This covers the agency gift-aggregation queries added in E6:
 *   GiftLog::where('agency_id', $id)
 *         ->where('created_at', '>=', now()->subDays(30))   -- or whereBetween
 *         ->selectRaw('SUM(giftPrice) as exp, receiver_id / sender_id')
 *         ->groupBy(...)
 *
 * Without this index MySQL must scan the full table after the agency_id lookup.
 * The composite index lets the engine satisfy both the equality filter on
 * agency_id and the range filter on created_at in a single B-tree traversal.
 *
 * Idempotent: checks for an existing index with the same leading columns
 * before creating, so it is safe to run multiple times.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_gift_logs_agency_id_created_at';

    public function up(): void
    {
        if ($this->compositeIndexExists('gift_logs', ['agency_id', 'created_at'])) {
            return;
        }

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if ($this->indexNameExists('gift_logs', self::INDEX_NAME)) {
            Schema::table('gift_logs', function (Blueprint $table) {
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
