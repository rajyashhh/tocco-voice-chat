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
        DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_aggregated AS
            SELECT
                cgu.user_id,
                u.uuid as user_uuid,
                cgu.game_id,
                DATE(cgu.created_at) as date,
                SUM(cgu.coins) as total_played,
                SUM(CASE WHEN cgu.type = 0 THEN cgu.coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END) as total_win,
                (SUM(cgu.coins) - SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END)) as app_profit
            FROM coin_game_users cgu
            LEFT JOIN users u ON u.id = cgu.user_id
            GROUP BY cgu.user_id, u.uuid, cgu.game_id, DATE(cgu.created_at)

            UNION ALL

            SELECT
                cgua.user_id,
                u.uuid as user_uuid,
                cgua.game_id,
                DATE(cgua.created_at) as date,
                SUM(cgua.coins) as total_played,
                SUM(CASE WHEN cgua.type = 0 THEN cgua.coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN cgua.type = 1 THEN cgua.coins ELSE 0 END) as total_win,
                (SUM(cgua.coins) - SUM(CASE WHEN cgua.type = 1 THEN cgua.coins ELSE 0 END)) as app_profit
            FROM coin_game_users_archive cgua
            LEFT JOIN users u ON u.id = cgua.user_id
            GROUP BY cgua.user_id, u.uuid, cgua.game_id, DATE(cgua.created_at)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_aggregated");
    }
};
