<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLiveTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_times', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('uid')->comment('room uid');
            $table->unsignedInteger('start_time');
            $table->string('end_time')->nullable();
            $table->string('hours')->nullable();
            $table->string('days')->nullable();
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
        Schema::dropIfExists('live_times');
    }
}
