<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal/intent row for the lucky-gift BATCH path. One row per batch combo,
 * written atomically with the upfront full-batch debit, so a SIGKILL/OOM in the
 * debit→batchSettle→dispatch window always leaves a findable trace that
 * lucky:reconcile-intents can compensate (refund or credit) instead of the
 * loss being silent.
 *
 * Status lifecycle:
 *   debited   — upfront debit committed (same tx as this row)
 *   settled   — batchSettle EVAL confirmed committed (total_paid recorded)
 *   completed — post-job money tx applied the win credit (terminal)
 *   refunded  — draw/settle window failed before the EVAL; debit refunded (terminal)
 *   reconciled— lucky:reconcile-intents compensated an orphan (terminal)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_batch_intents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('nonce')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cost');
            $table->unsignedBigInteger('total_paid')->nullable();
            $table->string('status', 20)->default('debited');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_batch_intents');
    }
};
