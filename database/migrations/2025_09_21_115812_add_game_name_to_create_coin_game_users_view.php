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
            CREATE OR REPLACE VIEW coin_game_users_all AS
            SELECT 
                c.id,
                c.user_id,
                c.coins,
                c.type,
                c.game_id,
                g.name AS game_name,
                g.image AS game_image,
                c.round_id,
                c.order_id,
                c.created_at,
                c.updated_at,
                c.app_profit_coins
            FROM (
                SELECT 
                    id,
                    user_id,
                    coins,
                    type,
                    game_id,
                    round_id,
                    order_id,
                    created_at,
                    updated_at,
                    app_profit_coins
                FROM coin_game_users

                UNION ALL

                SELECT 
                    id,
                    user_id,
                    coins,
                    type,
                    game_id,
                    round_id,
                    order_id,
                    created_at,
                    updated_at,
                    app_profit_coins
                FROM coin_game_users_archive
            ) c
            LEFT JOIN all_games g ON g.id = c.game_id
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_all");
    }
};
