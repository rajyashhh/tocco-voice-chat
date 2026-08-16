<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomBoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_booms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('total_room_gift_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('room_boom_level_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('total_gifts_value', '40')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('room_booms');
    }
}
