<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * §5.6-6 / §6.1: drop two indexes on the 12M-row fair_luck_transactions table:
 *   - is_winner index  (cardinality ~1 — misleads the optimizer, never selective).
 *   - the DUPLICATE created_at index: the table-create added
 *     `fair_luck_transactions_created_at_index`, and 2026_04_28 added
 *     `idx_flt_created_at` over the SAME column — keep idx_flt_created_at, drop the
 *     original (free write cost otherwise).
 * Existence-guarded so it is safe whatever the live index naming turns out to be.
 */
return new class extends Migration {
    public function up(): void
    {
        // is_winner: cardinality ~1, never selective — always safe to drop.
        $this->dropIfExists('fair_luck_transactions_is_winner_index');

        // The created_at index is a DUPLICATE only if the keeper idx_flt_created_at
        // actually exists live. Both index migrations are existence-guarded, so on
        // some environments idx_flt_created_at may have been skipped and the ORIGINAL
        // is the only created_at index. Never drop it unless the keeper is present —
        // otherwise the hot created_at workload (retention purge, monitor aggregates,
        // report range reads) is left unindexed on the 12M-row table → 504s.
        if ($this->hasIndex('idx_flt_created_at')) {
            $this->dropIfExists('fair_luck_transactions_created_at_index');
        }
    }

    public function down(): void
    {
        Schema::table('fair_luck_transactions', function ($table) {
            if (!$this->hasIndex('fair_luck_transactions_is_winner_index')) {
                $table->index('is_winner', 'fair_luck_transactions_is_winner_index');
            }
            if (!$this->hasIndex('fair_luck_transactions_created_at_index')) {
                $table->index('created_at', 'fair_luck_transactions_created_at_index');
            }
        });
    }

    private function dropIfExists(string $indexName): void
    {
        if ($this->hasIndex($indexName)) {
            Schema::table('fair_luck_transactions', function ($table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            $rows = DB::select('SHOW INDEX FROM `fair_luck_transactions` WHERE Key_name = ?', [$indexName]);
            return count($rows) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
