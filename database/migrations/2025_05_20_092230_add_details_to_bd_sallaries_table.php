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
        Schema::table('bd_sallaries', function (Blueprint $table) {
            $table->decimal('total_agency_sallary', 15, 2)->default(0);
            $table->decimal('total_users_sallary', 15, 2)->default(0);
            $table->decimal('total_diamond', 15, 2)->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('bd_sallaries', function (Blueprint $table) {
            $table->dropColumn(['total_agency_sallary', 'total_users_sallary', 'total_diamond']);
        });
    }
};
