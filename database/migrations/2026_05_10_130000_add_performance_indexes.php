<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Critical Performance Fix: Add missing indexes
 *
 * Root Cause Analysis (RCA) — 2026-05-10
 *
 * Problem #1: 20+ stuck queries on live_times table (each running 1+ hour!)
 *   - Correlated subquery does full table scan (74K rows) per user
 *   - No index on uid, start_time, or hours
 *
 * Problem #2: 61-second COUNT query on user_official_messages
 *   - Zero indexes beyond PK (267K rows)
 *   - Every JOIN/EXISTS does a full table scan
 *
 * Problem #3: Full table scans on user_diamond_logs (148K rows)
 *   - Zero indexes beyond PK
 *
 * Expected Impact:
 *   - live_times ranking query: from 1+ hour → under 1 second
 *   - user_official_messages COUNT: from 61s → milliseconds
 *   - user_diamond_logs: prevent full table scans
 *
 * Safe to run during live traffic — non-blocking DDL on tables under 300K rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. live_times — MOST CRITICAL
        // Fixes: 20+ stuck ranking queries (each running 1+ hour)
        // The query filters on uid + start_time and sums hours
        if (Schema::hasTable('live_times')) {
            Schema::table('live_times', function (Blueprint $table) {
                if (!$this->hasIndex('live_times', 'idx_live_times_uid_start')) {
                    $table->index(['uid', 'start_time', 'hours'], 'idx_live_times_uid_start');
                }
            });
        }

        // 2. user_official_messages — fixes 61-second COUNT query
        // The query joins on official_message_id + user_id
        if (Schema::hasTable('user_official_messages')) {
            Schema::table('user_official_messages', function (Blueprint $table) {
                if (!$this->hasIndex('user_official_messages', 'idx_uom_message_user')) {
                    $table->index(['official_message_id', 'user_id'], 'idx_uom_message_user');
                }
            });
        }

        // 3. user_diamond_logs — prevents full table scan
        // Common queries filter by user_id + created_at
        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                if (!$this->hasIndex('user_diamond_logs', 'idx_udl_user_created')) {
                    $table->index(['user_id', 'created_at'], 'idx_udl_user_created');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('live_times')) {
            Schema::table('live_times', function (Blueprint $table) {
                $table->dropIndex('idx_live_times_uid_start');
            });
        }

        if (Schema::hasTable('user_official_messages')) {
            Schema::table('user_official_messages', function (Blueprint $table) {
                $table->dropIndex('idx_uom_message_user');
            });
        }

        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                $table->dropIndex('idx_udl_user_created');
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
