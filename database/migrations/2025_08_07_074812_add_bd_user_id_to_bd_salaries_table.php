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
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->unsignedBigInteger('bd_user_id')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->dropColumn('bd_user_id');
        });
    }
};
