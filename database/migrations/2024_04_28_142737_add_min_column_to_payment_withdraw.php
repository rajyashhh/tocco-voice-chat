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
        Schema::table('payment_withdraw_types', function (Blueprint $table) {
            $table->double('min_value')->default(0);
            $table->integer('exchange_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_withdraw_types', function (Blueprint $table) {
            $table->dropColumn('min_value');
            $table->dropColumn('exchange_rate');
        });
    }
};
