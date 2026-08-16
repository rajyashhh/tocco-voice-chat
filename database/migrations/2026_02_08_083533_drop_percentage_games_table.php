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
        Schema::dropIfExists('percentage_games');
        Schema::dropIfExists('percentage_game_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
