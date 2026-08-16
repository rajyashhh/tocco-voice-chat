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
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('game_id')->index();
            $table->string('room_id')->index();
            $table->string('orderId')->nullable();

            $table->string('owner_uid')->nullable(); // uid of room owner
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->string('reason')->nullable(); // normal | timeout | forced
            $table->boolean('room_destroy')->default(false);

           $table->json('player_list')->nullable();
           $table->json('rankList')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
