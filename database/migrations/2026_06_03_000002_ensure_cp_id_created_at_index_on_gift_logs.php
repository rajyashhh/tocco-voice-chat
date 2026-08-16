<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure a composite (cp_id, created_at) index exists on gift_logs.
 *
 * This index serves CpRepository::getCpRankingWithOutRelation / getCpRanking, which filter
 * whereNotNull('cp_id') + whereBetween('gift_logs.created_at', ...) then GROUP BY cp_id.
 *
 * Idempotent and online (ALGORITHM=INPLACE, LOCK=NONE). An equivalent index
 * (idx_gl_cp_id_created) was already added in 2026_05_10_114100; this migration is a guarded
 * safety net for environments where that one was not applied. It checks the actual column
 * composition (cp_id, created_at as the leading two columns), not just a name, so it never
 * creates a duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->compositeIndexExists('gift_logs', ['cp_id', 'created_at'])) {
            return;
        }

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['cp_id', 'created_at'], 'idx_gift_logs_cp_id_created_at');
        });
    }

    public function down(): void
    {
        if ($this->indexNameExists('gift_logs', 'idx_gift_logs_cp_id_created_at')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropIndex('idx_gift_logs_cp_id_created_at');
            });
        }
    }

    /**
     * True if any index on the table starts with exactly the given leading columns.
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
