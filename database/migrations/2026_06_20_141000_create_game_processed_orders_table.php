<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Durable idempotency store for balance-moving game webhooks
     * (sit-down debit, game-end credit). The DB unique constraint is the
     * source of truth for "already processed"; a duplicate insert (unique
     * violation) inside the balance transaction means the provider replayed
     * the same orderId and we must NOT move balance again.
     */
    public function up(): void
    {
        Schema::create('game_processed_orders', function (Blueprint $table) {
            $table->id();
            // Provider's unique transaction id per endpoint. Scoped by endpoint
            // because the same orderId space is not guaranteed unique across
            // different webhook actions. Length 191 matches the orderId|max:191
            // request validation and keeps the (endpoint, order_id) unique index
            // within InnoDB key-length limits on any MySQL version.
            $table->string('order_id', 191);
            $table->string('endpoint', 32);
            $table->timestamp('created_at')->nullable();

            $table->unique(['endpoint', 'order_id'], 'game_processed_orders_endpoint_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_processed_orders');
    }
};
