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
        if (!Schema::hasTable('percentage_games')) {
            Schema::create('percentage_games', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->decimal('percentage_game', 5, 2)->default(2.00);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('percentage_game_users')) {
            Schema::create('percentage_game_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('percentage_game_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percentage_game_users');
        Schema::dropIfExists('percentage_games');
    }
};
