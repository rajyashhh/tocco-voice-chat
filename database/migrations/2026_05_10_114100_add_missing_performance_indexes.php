<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Critical Performance Fix: Add missing database indexes
 *
 * Problem: Full table scans on every query causing 1-142 second response times.
 * Evidence: Jo server — one query scanning 21.6M rows took 2 min 22 sec.
 *
 * These indexes are non-blocking on tables under 300K rows and safe to run live.
 *
 * @see server-optimization-report.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. official_messages — fixes the 2.5-minute query and all 1-4 sec queries
        //    Every request filtering by user_id + type currently full-scans 120K rows
        Schema::table('official_messages', function (Blueprint $table) {
            // Check if index doesn't already exist before adding
            if (!$this->hasIndex('official_messages', 'idx_om_user_type_created')) {
                $table->index(['user_id', 'type', 'created_at'], 'idx_om_user_type_created');
            }
            if (!$this->hasIndex('official_messages', 'idx_om_type_feature')) {
                $table->index(['type', 'feature'], 'idx_om_type_feature');
            }
        });

        // 2. user_official_messages — fixes the subquery join in the worst query
        //    276K rows with zero indexes beyond PK
        Schema::table('user_official_messages', function (Blueprint $table) {
            if (!$this->hasIndex('user_official_messages', 'idx_uom_msg_user')) {
                $table->index(['official_message_id', 'user_id'], 'idx_uom_msg_user');
            }
        });

        // 3. live_times — fixes 1.3 sec full scans on every room entry
        //    79K rows with zero indexes beyond PK
        Schema::table('live_times', function (Blueprint $table) {
            if (!$this->hasIndex('live_times', 'idx_lt_uid_created')) {
                $table->index(['uid', 'created_at'], 'idx_lt_uid_created');
            }
        });

        // 4. gift_logs — fixes CP lovely ranking queries (1.8 sec, 150K row scan)
        Schema::table('gift_logs', function (Blueprint $table) {
            if (!$this->hasIndex('gift_logs', 'idx_gl_cp_id_created')) {
                $table->index(['cp_id', 'created_at'], 'idx_gl_cp_id_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('official_messages', function (Blueprint $table) {
            $table->dropIndex('idx_om_user_type_created');
            $table->dropIndex('idx_om_type_feature');
        });

        Schema::table('user_official_messages', function (Blueprint $table) {
            $table->dropIndex('idx_uom_msg_user');
        });

        Schema::table('live_times', function (Blueprint $table) {
            $table->dropIndex('idx_lt_uid_created');
        });

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropIndex('idx_gl_cp_id_created');
        });
    }

    /**
     * Check if an index exists on a table
     */
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
