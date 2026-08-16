<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable per-request idempotency record for lucky-gift sends. The client
 * attaches a UUID nonce per request; the unique (user_id, nonce) insert is the
 * claim. A retry of the same nonce either replays the stored response
 * byte-identically (response set) or is refused as in-progress (response null)
 * — it can NEVER re-charge. Durable (DB, not TTL'd Redis) so a SIGKILL after
 * money committed still leaves the claim standing. Pruned after 24h by
 * lucky:reconcile-intents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_request_nonces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('nonce');
            $table->mediumText('response')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'nonce']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_request_nonces');
    }
};
