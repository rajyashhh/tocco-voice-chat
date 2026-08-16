<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoxUsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('box_uses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('box_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('coins')->nullable();
            $table->unsignedBigInteger('end_at')->nullable();
            $table->unsignedBigInteger('room_uid')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->integer('users_num')->default(1)->nullable();
            $table->unsignedTinyInteger('type')->comment('0=local 1=global')->nullable();
            $table->string('label')->nullable();
            $table->integer('used_num')->nullable();
            $table->integer('not_used_num')->nullable();
            $table->string ('image')->nullable ();
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
        Schema::dropIfExists('box_uses');
    }
}
