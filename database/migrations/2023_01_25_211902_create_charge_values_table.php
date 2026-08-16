<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargeValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_values', function (Blueprint $table) {
            $table->increments('id');
            $table->double('usd')->nullable();
            $table->double('value')->nullable();
            $table->unsignedTinyInteger('type')->comment('0=coins 1=selver coins')->nullable();
            $table->string('usd_img')->nullable();
            $table->string('type_img')->nullable();
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
        Schema::dropIfExists('charge_values');
    }
}
