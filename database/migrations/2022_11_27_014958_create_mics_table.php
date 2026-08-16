<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mics', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('roomowner_id')->comment('room ouner')->nullable();
            $table->unsignedInteger('user_id')->comment('on mic user')->nullable();
            $table->unsignedTinyInteger('type')->default('1')->comment('1 ordinary row of mic 2 point single row of mic')->nullable();
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
        Schema::dropIfExists('mics');
    }
}
