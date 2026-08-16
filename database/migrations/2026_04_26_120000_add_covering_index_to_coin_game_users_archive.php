<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->index(['game_id', 'user_id', 'type', 'coins', 'created_at'], 'cgu_archive_covering_index');
        });
    }

    public function down(): void
    {
        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->dropIndex('cgu_archive_covering_index');
        });
    }
};
