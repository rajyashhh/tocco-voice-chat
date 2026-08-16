<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOVipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('o_vips', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('level')->nullable();
            $table->string('name')->nullable();
            $table->string('img')->nullable();
            $table->double('price')->nullable();
            $table->string('privileges')->nullable();
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
        Schema::dropIfExists('o_vips');
    }
}
