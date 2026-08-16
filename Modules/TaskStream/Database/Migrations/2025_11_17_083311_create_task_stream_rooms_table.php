<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskStreamRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_stream_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_stream_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('room_id');

            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->unique(['task_stream_id', 'room_id'], 'ux_task_room');
            $table->index('room_id', 'idx_task_rooms_room');
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
        Schema::dropIfExists('task_stream_rooms');
    }
}
