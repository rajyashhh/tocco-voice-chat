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
        Schema::create('record_room_game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_room_game_id')->constrained('record_room_games', 'id')->onDelete('cascade');
            $table->integer("round_number");
            $table->unsignedBigInteger('player_win_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_room_game_rounds');
    }
};
