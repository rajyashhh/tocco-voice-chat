<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Covering composite index for the Host Diamond admin report
 * (App\Admin\Controllers\AgencyControllers\HostDiamondController::grid()):
 *
 *   GiftLog::selectRaw('receiver_id, agency_id, SUM(giftPrice) as total_gift_price')
 *          ->where('agency_id', '!=', 0)
 *          ->where('created_at', '>=', $from)   // default startOfMonth
 *          ->where('created_at', '<=', $to)     // default endOfMonth
 *          ->groupBy('receiver_id', 'agency_id')
 *          ->orderByDesc('total_gift_price');
 *
 * This report ranges over created_at across ALL agencies (agency_id != 0),
 * so the existing (agency_id, created_at) index cannot be used to satisfy the
 * date range (its leading column is the equality column, not present here).
 *
 * Leading the index with created_at lets the engine range-scan the bounded
 * date window, while including agency_id + receiver_id (the GROUP BY keys) and
 * giftPrice (the SUM input) makes the index covering: the aggregation runs
 * entirely from the B-tree with no row lookups.
 *
 * Additive only. Idempotent: skips creation if an index with the same leading
 * columns already exists. Reversible.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_gift_logs_created_agency_receiver_price';

    private const COLUMNS = ['created_at', 'agency_id', 'receiver_id', 'giftPrice'];

    public function up(): void
    {
        if ($this->compositeIndexExists('gift_logs', ['created_at', 'agency_id', 'receiver_id'])) {
            return;
        }

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(self::COLUMNS, self::INDEX_NAME);
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
