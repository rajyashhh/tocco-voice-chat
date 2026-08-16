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
        Schema::create('payment_method_histories', function (Blueprint $table) {
            $table->id();
            $table->string("ref_code")->nullable();
            $table->double("amount")->default(0);
            $table->string("payment_method")->default("fawry")->nullable();
            $table->string("status")->default("pending");
            $table->string('type')->nullable();
            $table->string('utd_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_histories');
    }
};
