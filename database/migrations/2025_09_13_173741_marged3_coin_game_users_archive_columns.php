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

    }
};