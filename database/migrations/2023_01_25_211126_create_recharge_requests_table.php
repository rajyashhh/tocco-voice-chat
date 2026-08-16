<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRechargeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recharge_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('charger_id')->nullable();
            $table->double('value_usd')->nullable();
            $table->unsignedTinyInteger('status')->comment('0=pending  1=accepted  2=denied')->nullable();
            $table->double('type_value')->comment('coins value or selver coins value')->nullable();
            $table->unsignedTinyInteger('type')->comment('0=coins 1=selver_coins')->nullable();
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
        Schema::dropIfExists('recharge_requests');
    }
}
