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
        Schema::create('payment_withdraw_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId ('payment_withdraw_type_id')->constrained('payment_withdraw_types')->onDelete ('cascade');
            $table->string("name");
            $table->string("type",100);
            $table->string("validation")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_withdraw_fields');
    }
};
