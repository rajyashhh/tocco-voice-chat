<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVipAuthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vip_auth', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->default('3')->comment('3vip5 guardian cp')->nullable();
            $table->unsignedInteger('level')->comment('المستوى المطلوب')->nullable();
            $table->unsignedTinyInteger('enable')->default('1')->comment('1 enable 2 disable')->nullable();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('img_0')->nullable();
            $table->string('img_1')->nullable();
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
        Schema::dropIfExists('vip_auth');
    }
}
