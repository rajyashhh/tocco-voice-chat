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
        Schema::create('coin_game_users_daily_aggregated', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id');
            $table->date('date');
            $table->bigInteger('total_played')->default(0);
            $table->bigInteger('total_loss')->default(0);
            $table->bigInteger('total_win')->default(0);
            $table->bigInteger('app_profit')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'game_id', 'date'], 'unique_user_game_date');
            $table->index(['date', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_game_users_daily_aggregated');
    }
};
