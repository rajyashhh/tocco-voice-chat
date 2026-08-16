<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletTables extends Migration
{
    public function up()
    {
        Schema::create('processed_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique()->index();
            $table->timestamp('processed_at')->index();
            $table->timestamps();

            $table->index(['idempotency_key', 'processed_at']);
        });

        Schema::create('wallet_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['credit', 'debit', 'transfer_in', 'transfer_out']);
            $table->string('reason')->nullable();
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->string('idempotency_key')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('container_id')->nullable();
            $table->timestamp('created_at')->index();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->index(['idempotency_key', 'user_id']);
            $table->index(['transaction_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_audit_log');
        Schema::dropIfExists('processed_transactions');
    }
}
