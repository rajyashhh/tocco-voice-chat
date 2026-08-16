<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agencies Performance Fix: Add missing indexes
 *
 * Problem: agencies/* endpoints taking 1-8.7 seconds
 * Root cause: bd_agency_host_sallaries table (2,579 rows) has ZERO indexes beyond PK
 * Evidence: HighLatencyP99 alert — P99 latency = 5.79s (threshold: 5s)
 *
 * Safe to run during live traffic — non-blocking DDL on small tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // bd_agency_host_sallaries — 2,579 rows with only PRIMARY key
        // Fixes agencies/details, agencies/history, agencies/target-details (1-8.7 sec)
        if (Schema::hasTable('bd_agency_host_sallaries')) {
            Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
                // Composite index for agency lookups with date filtering
                if (!$this->hasIndex('bd_agency_host_sallaries', 'idx_bahs_agency_created')) {
                    $table->index(['agency_id', 'created_at'], 'idx_bahs_agency_created');
                }
                // Index for BD filtering (used in BdController profile page)
                if (!$this->hasIndex('bd_agency_host_sallaries', 'idx_bahs_bd_id')) {
                    $table->index(['bd_id'], 'idx_bahs_bd_id');
                }
                // Composite index for BD salary queries with year + amount filter
                if (!$this->hasIndex('bd_agency_host_sallaries', 'idx_bahs_bd_year_amount')) {
                    $table->index(['bd_id', 'year', 'amount'], 'idx_bahs_bd_year_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bd_agency_host_sallaries')) {
            Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
                $table->dropIndex('idx_bahs_agency_created');
                $table->dropIndex('idx_bahs_bd_id');
                $table->dropIndex('idx_bahs_bd_year_amount');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
