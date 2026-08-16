<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmojisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emojis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->comment('parent class id')->nullable();
            $table->string('name')->nullable();
            $table->string('emoji')->comment('static image')->nullable();
            $table->unsignedTinyInteger('t_length')->comment('Duration (seconds)')->nullable();
            $table->unsignedTinyInteger('enable')->default('1')->comment('1 enable 2 disable')->nullable();
            $table->unsignedTinyInteger('sort')->comment('to sort')->nullable();
            $table->unsignedInteger('addtime')->nullable();
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
        Schema::dropIfExists('emojis');
    }
}
