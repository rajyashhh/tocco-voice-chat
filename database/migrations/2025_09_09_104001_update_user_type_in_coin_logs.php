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
        DB::table('coin_logs')
            ->where('user_type', 'user')
            ->update(['user_type' => 'App\\Models\\User']);

        DB::table('coin_logs')
            ->where('user_type', 'shipping_agency')
            ->update(['user_type' => 'App\\Models\\ShippingAgency']);

        Schema::table('coin_logs', function (Blueprint $table) {
            $table->string('user_type')
                  ->default('App\\Models\\User')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('coin_logs', function (Blueprint $table) {
            $table->string('user_type')->nullable()->default(null)->change();
        });
    }
};
