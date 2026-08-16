<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds indexes to eliminate full table scans on:
     * - user_official_messages
     * - user_diamond_logs
     * - live_times
     * - users_vips
     */
    public function up(): void
    {
        // user_official_messages indexes
        Schema::table('user_official_messages', function (Blueprint $table) {
            $table->index('user_id', 'idx_user_official_messages_user_id');
            $table->index('official_message_id', 'idx_user_official_messages_official_message_id');
            $table->index('created_at', 'idx_user_official_messages_created_at');
        });

        // user_diamond_logs indexes (if table exists)
        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                $table->index('user_id', 'idx_user_diamond_logs_user_id');
                $table->index('type', 'idx_user_diamond_logs_type');
                $table->index('get_by_id', 'idx_user_diamond_logs_get_by_id');
                $table->index('created_at', 'idx_user_diamond_logs_created_at');
                // Composite index for common queries: get user's logs by type
                $table->index(['user_id', 'type'], 'idx_user_diamond_logs_user_type');
            });
        }

        // live_times indexes
        Schema::table('live_times', function (Blueprint $table) {
            $table->index('uid', 'idx_live_times_uid');
            $table->index('user_id', 'idx_live_times_user_id');
            $table->index('start_time', 'idx_live_times_start_time');
            $table->index('created_at', 'idx_live_times_created_at');
            // Composite index for date range queries on created_at with uid
            $table->index(['created_at', 'uid'], 'idx_live_times_created_uid');
        });

        // users_vips indexes
        Schema::table('users_vips', function (Blueprint $table) {
            $table->index('user_id', 'idx_users_vips_user_id');
            $table->index('vip_id', 'idx_users_vips_vip_id');
            $table->index('sender_id', 'idx_users_vips_sender_id');
            $table->index('level', 'idx_users_vips_level');
            $table->index('expire', 'idx_users_vips_expire');
            // Composite index for active VIP lookup: user_id + expire >= X ORDER BY level DESC
            $table->index(['user_id', 'expire', 'level'], 'idx_users_vips_user_expire_level');
            // Composite index for type-based queries
            $table->index(['type', 'user_id'], 'idx_users_vips_type_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_official_messages', function (Blueprint $table) {
            $table->dropIndex('idx_user_official_messages_user_id');
            $table->dropIndex('idx_user_official_messages_official_message_id');
            $table->dropIndex('idx_user_official_messages_created_at');
        });

        if (Schema::hasTable('user_diamond_logs')) {
            Schema::table('user_diamond_logs', function (Blueprint $table) {
                $table->dropIndex('idx_user_diamond_logs_user_id');
                $table->dropIndex('idx_user_diamond_logs_type');
                $table->dropIndex('idx_user_diamond_logs_get_by_id');
                $table->dropIndex('idx_user_diamond_logs_created_at');
                $table->dropIndex('idx_user_diamond_logs_user_type');
            });
        }

        Schema::table('live_times', function (Blueprint $table) {
            $table->dropIndex('idx_live_times_uid');
            $table->dropIndex('idx_live_times_user_id');
            $table->dropIndex('idx_live_times_start_time');
            $table->dropIndex('idx_live_times_created_at');
            $table->dropIndex('idx_live_times_created_uid');
        });

        Schema::table('users_vips', function (Blueprint $table) {
            $table->dropIndex('idx_users_vips_user_id');
            $table->dropIndex('idx_users_vips_vip_id');
            $table->dropIndex('idx_users_vips_sender_id');
            $table->dropIndex('idx_users_vips_level');
            $table->dropIndex('idx_users_vips_expire');
            $table->dropIndex('idx_users_vips_user_expire_level');
            $table->dropIndex('idx_users_vips_type_user');
        });
    }
};
