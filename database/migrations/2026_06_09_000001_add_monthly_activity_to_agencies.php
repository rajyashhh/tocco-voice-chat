<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalize "most active agency this month" onto the agencies table.
 *
 * monthly_activity stores the SUM(gift_logs.giftPrice) credited to an agency
 * (gift_logs.agency_id) for the CURRENT calendar month. It is maintained by the
 * scheduled command agency:update-monthly-activity (hourly + month-start reset),
 * so the agency listing endpoint can ORDER BY a cheap indexed column instead of
 * recomputing a correlated subquery over gift_logs on every request.
 *
 * The composite index covers the primary sort key of agency_filter
 * (monthly_activity DESC, then id as a stable tiebreaker). mempers_count is a
 * computed withCount() aggregate (not a stored column) so it is sorted in
 * memory and intentionally left out of the index.
 *
 * Idempotent: safe to run more than once.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_agencies_monthly_activity_sort';

    public function up(): void
    {
        if (!Schema::hasColumn('agencies', 'monthly_activity')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->decimal('monthly_activity', 16, 2)->default(0)->after('is_frozen');
            });
        }

        if (!$this->indexNameExists('agencies', self::INDEX_NAME)) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->index(['monthly_activity', 'id'], self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexNameExists('agencies', self::INDEX_NAME)) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        if (Schema::hasColumn('agencies', 'monthly_activity')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropColumn('monthly_activity');
            });
        }
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
