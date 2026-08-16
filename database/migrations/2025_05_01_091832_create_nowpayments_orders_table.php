<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nowpayments_orders', function (Blueprint $table) {
            $table->id();
            $table->longText('payment_id');
            $table->longText('pay_address');
            $table->string('payment_status');
            $table->string('pay_currency');
            $table->double('pay_amount');
            $table->double('amount_received');
            $table->double('price_amount');
            $table->string('price_currency')->default('usd');
            $table->longText('order_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nowpayments_orders');
    }
};
