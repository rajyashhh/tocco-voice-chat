<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance fix for slow queries identified on 2026-05-24
     * - Problem #1: monthly_diamond_receives scanning 4.5M rows
     * - Problem #3: family_user correlated subqueries
     * - Problem #5: rooms uid lookups
     */
    public function up(): void
    {
        // P1: Most critical - fixes 4.5M row scans on agency members query
        // Expected improvement: 4,532,593 rows → ~200 rows, 2sec → 50ms
        Schema::table('monthly_diamond_receives', function (Blueprint $table) {
            // Covering index: user_id + month + year for filtering, monthly_diamond_received for sorting
            $table->index(
                ['user_id', 'month', 'year', 'monthly_diamond_received'],
                'idx_mdr_user_month_year_amount'
            );
        });

        // P3: Families page performance - correlated subquery optimization
        // Expected improvement: 71,214 rows → ~100 rows
        Schema::table('family_user', function (Blueprint $table) {
            $table->index(
                ['family_id', 'status', 'user_type'],
                'idx_fu_family_status_type'
            );
        });

        // P3: Countries hot rooms count optimization
        // Check if index exists first to avoid errors
        if (!$this->indexExists('rooms', 'idx_rooms_uid')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->index('uid', 'idx_rooms_uid');
            });
        }

        // P2: Gift logs sender performance for country supporters leaderboard
        // Expected improvement: 309,904 rows → ~50 rows, 1.5sec → 10ms
        if (!$this->indexExists('gift_logs', 'idx_gl_sender_giftprice')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->index(['sender_id', 'giftPrice'], 'idx_gl_sender_giftprice');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_diamond_receives', function (Blueprint $table) {
            $table->dropIndex('idx_mdr_user_month_year_amount');
        });

        Schema::table('family_user', function (Blueprint $table) {
            $table->dropIndex('idx_fu_family_status_type');
        });

        if ($this->indexExists('rooms', 'idx_rooms_uid')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropIndex('idx_rooms_uid');
            });
        }

        if ($this->indexExists('gift_logs', 'idx_gl_sender_giftprice')) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropIndex('idx_gl_sender_giftprice');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
