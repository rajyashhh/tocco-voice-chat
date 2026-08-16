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
            Schema::table('user_coin_logs', function (Blueprint $table) {
                $table->string('user_type')->nullable();
            });

            Schema::table('coin_logs', function (Blueprint $table) {
                $table->string('user_type')->nullable();
            });
            
            Schema::table('payment_coins', function (Blueprint $table) {
                $table->string('package_type')
                      ->nullable();
            });
        }
    
        public function down(): void
        {
            Schema::table('user_coin_logs', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
            Schema::table('coin_logs', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
    
            Schema::table('payment_coins', function (Blueprint $table) {
                $table->dropColumn('package_type');
            });
        }
    
    
};
