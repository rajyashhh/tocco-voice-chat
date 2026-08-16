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
        
            Schema::table('targets', function (Blueprint $table) {
                $table->decimal('app_profit_percentage', 5, 2)->default(0);
                $table->decimal('db_percentage', 5, 2)->default(0);
            });
        }
    
        public function down(): void
        {
            Schema::table('targets', function (Blueprint $table) {
                $table->dropColumn(['app_profit_percentage', 'db_percentage']);
            });
        }
};
