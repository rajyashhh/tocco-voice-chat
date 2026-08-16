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
        Schema::create('core_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('from_wallet')->constrained('wallets');
            $table->string('to_wallet')->constrained('wallets');
            $table->string('admin_id')->constrained('admin_users');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_wallet_transactions');
    }
};
