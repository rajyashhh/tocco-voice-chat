<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Fix: Add missing indexes on high-traffic tables.
 *
 * Problem:
 * - user_diamond_logs (148K rows, 13.5MB) has NO index besides PRIMARY
 * - users_vips (23K rows, 5.5MB) has NO index besides PRIMARY
 *
 * These tables are frequently queried by user_id, causing full table scans.
 *
 * Solution: Add proper indexes on the columns used in WHERE/JOIN clauses.
 */
return new class extends Migration
{
    public function up(): void
    {
        // user_diamond_logs — 148K rows
        if (!$this->hasIndex('user_diamond_logs', 'idx_udl_user_created')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_udl_user_created');
            });
        }

        // users_vips — 23K rows
        if (!$this->hasIndex('users_vips', 'idx_uv_user_id')) {
            Schema::table('users_vips', function (Blueprint $table) {
                $table->index('user_id', 'idx_uv_user_id');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('user_diamond_logs', 'idx_udl_user_created')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                $table->dropIndex('idx_udl_user_created');
            });
        }

        if ($this->hasIndex('users_vips', 'idx_uv_user_id')) {
            Schema::table('users_vips', function (Blueprint $table) {
                $table->dropIndex('idx_uv_user_id');
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
