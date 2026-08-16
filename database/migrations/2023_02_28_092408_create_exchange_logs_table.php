<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger ('user_id');
            $table->unsignedBigInteger ('diamonds')->nullable ()->default (0);
            $table->double ('value')->nullable ()->default (0);
            $table->unsignedTinyInteger ('type')->nullable ()->default (0);
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
        Schema::dropIfExists('exchange_logs');
    }
}
