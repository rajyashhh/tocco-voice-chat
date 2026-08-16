<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the CP-pairing column `cp_id` to gift_logs.
 *
 * cp_id is read/written by the CP module (CpRepository::getCpRankingWithOutRelation
 * joins gift_logs.cp_id -> cps.id for the couple ranking, and the gift send path
 * stamps it). It exists on established installs but was never added by any repo
 * migration, so a fresh build (e.g. this voice edition) lacks it and every request
 * that ranks couples fails with "Unknown column 'cp_id'". This adds it from the
 * root — matching production (`bigint unsigned NULL`) — plus the composite
 * (cp_id, created_at) index the ranking query relies on (the dedicated index
 * migration skips when the column is absent, so we create it here too).
 *
 * Idempotent: guarded by hasColumn / index-composition checks, safe to re-run and
 * a no-op on installs that already have the column and index.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('gift_logs', 'cp_id')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('cp_id')->nullable();
            });
        }

        if (!$this->compositeIndexExists('gift_logs', ['cp_id', 'created_at'])) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->index(['cp_id', 'created_at'], 'idx_gift_logs_cp_id_created_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexNameExists('gift_logs', 'idx_gift_logs_cp_id_created_at')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropIndex('idx_gift_logs_cp_id_created_at');
            });
        }

        if (Schema::hasColumn('gift_logs', 'cp_id')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropColumn('cp_id');
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
