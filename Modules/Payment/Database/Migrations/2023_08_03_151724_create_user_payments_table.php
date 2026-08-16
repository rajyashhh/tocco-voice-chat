<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Payment\Enums\PaymentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_coin_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedInteger('coin_id')->unsigned()->index();
            $table->foreign('coin_id')->references('id')->on('coins')->onDelete('cascade')->onUpdate('cascade');
            $table->string('reference_id')->nullable();
            $table->string('order_no')->nullable();
            $table->string('status')->default(PaymentStatus::INITIAL);

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
        Schema::dropIfExists('user_coin_payments');
    }
};
