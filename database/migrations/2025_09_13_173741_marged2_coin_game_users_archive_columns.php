<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة indexes بأسماء جديدة لتجنب أي duplicates
        Schema::table('coin_game_users', function (Blueprint $table) {
            $table->index('user_id', 'idx_cgu_user_id');
            $table->index('created_at', 'idx_cgu_created_at');
            $table->index(['user_id', 'created_at'], 'idx_cgu_user_created');
            $table->index('type', 'idx_cgu_type');
        });

        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->index('user_id', 'idx_cgua_user_id');
            $table->index('created_at', 'idx_cgua_created_at');
            $table->index(['user_id', 'created_at'], 'idx_cgua_user_created');
            $table->index('type', 'idx_cgua_type');
        });

        DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_merged AS
            SELECT 
                user_id,
                DATE(created_at) as date,
                type,
                SUM(coins) as coins
            FROM coin_game_users
            GROUP BY user_id, DATE(created_at), type

            UNION ALL

            SELECT 
                user_id,
                DATE(created_at) as date,
                type,
                SUM(coins) as coins
            FROM coin_game_users_archive
            GROUP BY user_id, DATE(created_at), type
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_merged");

        Schema::table('coin_game_users', function (Blueprint $table) {
            $table->dropIndex('idx_cgu_user_id');
            $table->dropIndex('idx_cgu_created_at');
            $table->dropIndex('idx_cgu_user_created');
            $table->dropIndex('idx_cgu_type');
        });

        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->dropIndex('idx_cgua_user_id');
            $table->dropIndex('idx_cgua_created_at');
            $table->dropIndex('idx_cgua_user_created');
            $table->dropIndex('idx_cgua_type');
        });
    }
};