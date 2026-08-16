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
        Schema::table('user_payment_gateways', function (Blueprint $table) {

            $table->integer('agency_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_payment_gateways', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('agency_id');
        });
    }
};
