<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->increments('id');
            $table->double('usd')->nullable();
            $table->unsignedBigInteger('coin')->nullable();
            $table->unsignedBigInteger('first_charge_coin')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('discount_code')->nullable();
            $table->integer('discount_code_expire_in')->comment('days')->nullable();
            $table->unsignedBigInteger('extra_value')->nullable();
            $table->integer('extra_value_end_in')->comment('days')->nullable();
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
        Schema::dropIfExists('coins');
    }
}
