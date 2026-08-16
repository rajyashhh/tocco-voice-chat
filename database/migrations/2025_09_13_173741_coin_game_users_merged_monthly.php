<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
        public function up(): void
        {
            DB::statement("
            CREATE OR REPLACE VIEW coin_game_users_merged_monthly AS
            SELECT 
                user_id,
                DATE_FORMAT(created_at, '%Y-%m') as month,
                type,
                SUM(coins) as coins
            FROM coin_game_users
            GROUP BY user_id, DATE_FORMAT(created_at, '%Y-%m'), type

            UNION ALL

            SELECT 
                user_id,
                DATE_FORMAT(created_at, '%Y-%m') as month,
                type,
                SUM(coins) as coins
            FROM coin_game_users_archive
            GROUP BY user_id, DATE_FORMAT(created_at, '%Y-%m'), type
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_merged_monthly");
    }
   
};