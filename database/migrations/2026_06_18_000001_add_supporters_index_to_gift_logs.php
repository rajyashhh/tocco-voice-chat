<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a composite (receiver_id, sender_id, giftPrice) index on gift_logs.
 *
 * Covers the supporters query (GiftLogRepository::getByUserId) behind the
 * profile "supporters" screen:
 *   GiftLog::where('receiver_id', $userId)
 *         ->selectRaw('sender_id, receiver_id, SUM(giftPrice) AS total')
 *         ->groupBy('sender_id', 'receiver_id')
 *         ->orderByDesc('total')->take(20)
 *
 * The existing (receiver_id, created_at, giftPrice) index only satisfies the
 * receiver_id equality — created_at sits between receiver_id and the GROUP BY
 * column, so MySQL still needed a temp table + filesort over sender_id on every
 * page open (full receiver-partition scan as gift_logs grows to millions).
 *
 * This index orders rows by (receiver_id, sender_id) so the engine reads the
 * receiver partition already grouped by sender_id and aggregates giftPrice from
 * the index leaf — no temp table, no filesort. take(20) then trims cheaply.
 *
 * Idempotent: checks for an existing index with the same leading columns first.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_gift_logs_receiver_sender_price';

    public function up(): void
    {
        if ($this->compositeIndexExists('gift_logs', ['receiver_id', 'sender_id', 'giftPrice'])) {
            return;
        }

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['receiver_id', 'sender_id', 'giftPrice'], self::INDEX_NAME);
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
