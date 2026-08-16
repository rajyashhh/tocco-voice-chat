<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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
                user_id,
                game_id,
                DATE(created_at) as date,
                SUM(coins) as total_played,
                SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_win,
                (SUM(coins) - SUM(CASE WHEN type = 1 THEN coins ELSE 0 END)) as app_profit
            FROM coin_game_users
            GROUP BY user_id, game_id, DATE(created_at)
    
            UNION ALL
    
            SELECT
                user_id,
                game_id,
                DATE(created_at) as date,
                SUM(coins) as total_played,
                SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_win,
                (SUM(coins) - SUM(CASE WHEN type = 1 THEN coins ELSE 0 END)) as app_profit
            FROM coin_game_users_archive
            GROUP BY user_id, game_id, DATE(created_at)
        ");
    }
    
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_aggregated");
    }
    
};
