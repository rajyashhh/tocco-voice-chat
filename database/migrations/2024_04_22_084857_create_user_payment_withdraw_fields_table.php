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
        Schema::create('user_payment_withdraw_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId ('user_id')->constrained('users')->onDelete ('cascade');
            $table->foreignId ('payment_withdraw_field_id')->constrained('payment_withdraw_fields')->onDelete ('cascade');
            $table->string("value");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_withdraw_fields');
    }
};
