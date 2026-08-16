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
        Schema::create('user_game_challanges', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("game_id");
            $table->bigInteger('room_id');
            $table->foreignId ('player_one_id')->constrained('users')->onDelete ('cascade');
            $table->foreignId ('player_two_id')->constrained('users')->onDelete ('cascade');
            $table->string("status")->default("waiting");
            $table->string("type")->default("waiting");
            $table->double("coins")->default(0);
            $table->string("answer_player_one")->nullable();
            $table->string("answer_player_two")->nullable();
            $table->bigInteger("player_win_id")->nullable();
            $table->string("note")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_game_challanges');
    }
};
