<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedTinyInteger('get_type')->default('1')->comment('Obtaining method 1 vip level automatic acquisition 2 activities 3 treasure box 4 purchase 5 = background addition')->nullable();
            $table->unsignedTinyInteger('type')->comment('1 gem 2 = gift 3 card roll 4 avatar frame 5 bubble frame 6 entry special effects 7 microphone aperture 8 badge')->nullable();
            $table->unsignedInteger('target_id')->nullable();
            $table->unsignedInteger('num')->default('1')->comment('quantity')->nullable();
            $table->unsignedInteger('expire')->comment('0 forever else is expiry time')->nullable();
            $table->unsignedTinyInteger('is_read')->comment('Whether read 0=read 1=unread')->nullable();
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
        Schema::dropIfExists('packs');
    }
}
