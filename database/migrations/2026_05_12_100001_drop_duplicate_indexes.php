<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix #5: Drop duplicate/useless indexes.
     * - gift_logs: 2 duplicate indexes (covered by composite indexes)
     * - coin_game_users: 3 duplicate indexes
     * - fair_luck_wallet_histories: 1 useless index (cardinality=1, user_id mostly NULL)
     * Saves ~65MB RAM and speeds up INSERT operations.
     */
    public function up(): void
    {
        // gift_logs: remove duplicate single-column indexes covered by composites
        Schema::table('gift_logs', function (Blueprint $table) {
            // gift_logs_receiver_id_index is duplicate of idx_gift_logs_receiver_id
            $table->dropIndex('gift_logs_receiver_id_index');
            // idx_gift_logs_sender_id is covered by gift_logs_sender_id_created_at_giftprice_index
            $table->dropIndex('idx_gift_logs_sender_id');
        });

        // coin_game_users: remove exact duplicate indexes
        Schema::table('coin_game_users', function (Blueprint $table) {
            // idx_cgu_user_id duplicates coin_game_users_user_id_foreign
            $table->dropIndex('idx_cgu_user_id');
            // idx_cgu_created_at duplicates coin_game_users_created_at_index
            $table->dropIndex('idx_cgu_created_at');
            // idx_cgu_user_created duplicates coin_game_users_user_id_created_at_index
            $table->dropIndex('idx_cgu_user_created');
        });

        // fair_luck_wallet_histories: remove useless user_id index (cardinality=1)
        Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
            $table->dropIndex('fair_luck_wallet_histories_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->index('receiver_id', 'gift_logs_receiver_id_index');
            $table->index('sender_id', 'idx_gift_logs_sender_id');
        });

        Schema::table('coin_game_users', function (Blueprint $table) {
            $table->index('user_id', 'idx_cgu_user_id');
            $table->index('created_at', 'idx_cgu_created_at');
            $table->index(['user_id', 'created_at'], 'idx_cgu_user_created');
        });

        Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
            $table->index('user_id', 'fair_luck_wallet_histories_user_id_index');
        });
    }
};
