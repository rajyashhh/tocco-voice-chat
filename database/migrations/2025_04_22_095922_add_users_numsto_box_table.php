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
        if (!Schema::hasColumn('boxs', 'dynamic_users_values')) {
            Schema::table('boxs', function (Blueprint $table) {
                $table->string('dynamic_users_values')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boxs', function (Blueprint $table) {
            $table->dropColumn('dynamic_users_values');
        });   
    
    }
};
