<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnteredRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entered_rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('uid');
            $table->unsignedBigInteger('ruid');
            $table->unsignedBigInteger('rid');
            $table->dateTime('entered_at')->nullable();
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
        Schema::dropIfExists('entered_rooms');
    }
}
