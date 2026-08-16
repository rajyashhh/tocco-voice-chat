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
            CREATE OR REPLACE VIEW coin_game_users_merged AS
            SELECT user_id, coins, type, created_at
            FROM coin_game_users
            UNION ALL
            SELECT user_id, coins, type, created_at
            FROM coin_game_users_archive
        ");
    }
    
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS coin_game_users_merged");
    }
    
};
