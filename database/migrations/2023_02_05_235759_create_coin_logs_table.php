<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->double('paid_usd')->nullable();
            $table->unsignedBigInteger('obtained_coins')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method')->nullable();
            $table->unsignedBigInteger('donor_id')->nullable();
            $table->string('donor_type')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
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
        Schema::dropIfExists('coin_logs');
    }
}
