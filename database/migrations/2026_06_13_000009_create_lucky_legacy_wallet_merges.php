<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S-ACC-5 / 7.2: durable audit for the one-shot merge of the legacy
 * jackpot/medium wallet rows (fair_luck_wallets id IN (2,3)) into the unified
 * vault.
 *
 * The crash window is between (1) zeroing the DB rows + claiming the audit and
 * (2) the Redis EVAL that adds the amount to unified_vault + baseline. If the
 * process dies in that window the zeroed wallet rows no longer carry the amount,
 * so the audit row stores merged_amount EXPLICITLY: the reconcile detector
 * (S-CON-3) completes the Redis side idempotently from this value alone.
 *
 * merge_key is a fixed idempotency token (one legacy merge ever) — UNIQUE makes
 * a second `lucky:merge-legacy-wallets` run a no-op instead of a double credit.
 * state: pending_redis (DB claimed, Redis not yet applied) → applied (terminal).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('lucky_legacy_wallet_merges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('merge_key', 64)->unique();
            $table->unsignedBigInteger('merged_amount');
            $table->string('state', 20)->default('pending_redis');
            $table->timestamps();

            $table->index(['state', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_legacy_wallet_merges');
    }
};
