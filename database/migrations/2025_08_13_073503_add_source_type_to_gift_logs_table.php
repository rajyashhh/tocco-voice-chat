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
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->string('source_type')->nullable()
                      ->comment('مصدر الماسات المرسلة: gift أو coins');
            });
        }
    
        public function down(): void
        {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropColumn('source_type');
            });
        }
    
    
};
