<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // View للجدول الحالي
        DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_aggregated_current AS
            SELECT
                cgu.user_id,
                u.uuid as user_uuid,
                u.name as user_name,
                up.avatar as user_avatar,
                cgu.game_id,
                g.name as game_name,
                g.image as game_image,
                DATE(cgu.created_at) as date,
                SUM(cgu.coins) as total_played,
                SUM(CASE WHEN cgu.type = 0 THEN cgu.coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END) as total_win,
                (SUM(CASE WHEN cgu.type = 0 THEN cgu.coins ELSE 0 END) - 
                 SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END)) as app_profit
            FROM coin_game_users cgu
            LEFT JOIN users u ON u.id = cgu.user_id
            LEFT JOIN profiles up ON up.user_id = u.id
            LEFT JOIN all_games g ON g.id = cgu.game_id
            GROUP BY cgu.user_id, u.uuid, u.name, up.avatar, cgu.game_id, g.name, g.image, DATE(cgu.created_at)
        ");

        // View للأرشيف
        DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_aggregated_archive AS
            SELECT
                cgua.user_id,
                u.uuid as user_uuid,
                u.name as user_name,
                up.avatar as user_avatar,
                cgua.game_id,
                g.name as game_name,
                g.image as game_image,
                DATE(cgua.created_at) as date,
                SUM(cgua.coins) as total_played,
                SUM(CASE WHEN cgua.type = 0 THEN cgua.coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN cgua.type = 1 THEN cgua.coins ELSE 0 END) as total_win,
                (SUM(CASE WHEN cgua.type = 0 THEN cgua.coins ELSE 0 END) - 
                 SUM(CASE WHEN cgua.type = 1 THEN cgua.coins ELSE 0 END)) as app_profit
            FROM coin_game_users_archive cgua
            LEFT JOIN users u ON u.id = cgua.user_id
            LEFT JOIN profiles up ON up.user_id = u.id
            LEFT JOIN all_games g ON g.id = cgua.game_id
            GROUP BY cgua.user_id, u.uuid, u.name, up.avatar, cgua.game_id, g.name, g.image, DATE(cgua.created_at)
        ");

        // View موحد يجمع الاتنين
        DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_aggregated AS
            SELECT * FROM coin_game_users_aggregated_current
            UNION ALL
            SELECT * FROM coin_game_users_aggregated_archive
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_aggregated");
        DB::statement("DROP VIEW IF EXISTS coin_game_users_aggregated_current");
        DB::statement("DROP VIEW IF EXISTS coin_game_users_aggregated_archive");
    }
};
