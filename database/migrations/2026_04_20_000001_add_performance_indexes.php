<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds critical indexes to improve query performance:
     * 1. gift_logs: Composite index for gift ranking queries
     * 2. user_lucky_gifts: Composite index for user gift lookups
     */
    public function up(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index(['room_id', 'created_at', 'giftPrice'], 'idx_gift_logs_room_ranking');
            $table->index(['agency_id', 'created_at', 'giftPrice'], 'idx_gift_logs_agency_ranking');
        });

        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->index(['user_id', 'gift_id'], 'idx_user_lucky_gifts_user_gift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropIndex('idx_gift_logs_room_ranking');
            $table->dropIndex('idx_gift_logs_agency_ranking');
        });

        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->dropIndex('idx_user_lucky_gifts_user_gift');
        });
    }
};
