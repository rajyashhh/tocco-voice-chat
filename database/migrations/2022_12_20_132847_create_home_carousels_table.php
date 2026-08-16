<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHomeCarouselsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('home_carousels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('img');
            $table->text('contents')->nullable();
            $table->string('url')->default('url')->nullable();
            $table->unsignedTinyInteger('enable')->default('1')->comment('1 enable 2 disable')->nullable();
            $table->unsignedTinyInteger('sort')->default('1')->nullable();
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
        Schema::dropIfExists('home_carousels');
    }
}
