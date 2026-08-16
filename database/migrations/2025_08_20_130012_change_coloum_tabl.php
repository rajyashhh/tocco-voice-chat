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
        Schema::table('payment_coins', function (Blueprint $table) {
            $table->string('package_type')->default('user')->change();
        });

        DB::table('payment_coins')
            ->whereNull('package_type')
            ->update(['package_type' => 'user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_coins', function (Blueprint $table) {
            // $table->string('package_type');
        });
    }
};
