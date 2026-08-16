<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('uid');
            $table->unsignedInteger('skill_id')->index();
            $table->unsignedInteger('service_time')->comment('service hours');
            $table->string('remark')->nullable();
            $table->unsignedInteger('addtime');
            $table->unsignedInteger('endtime')->comment('deadline')->nullable();
            $table->unsignedTinyInteger('status')->index()->default('1')->comment('1=valid 0=invalid')->nullable();
            $table->string('adduser')->comment('Operator')->nullable();
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
        Schema::dropIfExists('monads');
    }
}
