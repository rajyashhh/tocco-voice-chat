<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBoxGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_box_gifts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('box_uses_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('coins')->nullable();
            $table->unsignedBigInteger('room_uid')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedTinyInteger('type')->comment('0=local 1=global')->nullable();
            $table->unsignedBigInteger('box_uses_owner_id')->nullable();
            $table->string('image')->nullable();
            $table->string('label')->nullable();
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
        Schema::dropIfExists('user_box_gifts');
    }
}
