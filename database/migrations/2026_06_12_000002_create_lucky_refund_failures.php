<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable journal for lucky-gift money compensations on the SERIAL path
 * (the batch path has lucky_batch_intents). One row per failed/pending
 * compensation; the row claim (pending → applied, conditional UPDATE inside the
 * same tx as the balance increment) is the ONLY application path — exactly-once
 * even under ambiguous connection-lost commits and concurrent reconcile runs.
 *
 * type:  bet_refund — processBet failed after the guarded per-hit debit stood;
 *                     the debit must be returned to the sender.
 *        win_credit — settle committed the vault debit but the sender's DB win
 *                     credit did not confirm; the win must be paid.
 * state: pending → applied (terminal) | review (terminal, manual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_refund_failures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20)->default('bet_refund');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('gift_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('state', 20)->default('pending');
            $table->timestamps();

            $table->index(['state', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_refund_failures');
    }
};
