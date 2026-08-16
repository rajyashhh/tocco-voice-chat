<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * 
 */
class CreateWalletIdempotencyKeysTable extends Migration
{
    /**
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('operation'); 
            $table->decimal('amount', 12, 2);
            $table->timestamp('expires_at')->index(); 
            $table->timestamps();

            $table->index(['user_id', 'operation']);
        });
    }

    /**
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_idempotency_keys');
    }
}
