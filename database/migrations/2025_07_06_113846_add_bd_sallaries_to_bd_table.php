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
            $table->decimal('sallary', 20, 4)->default(0)->change();
            $table->decimal('cut_amount', 20, 4)->default(0)->change();
            $table->decimal('total_agency_sallary', 20, 4)->default(0)->change();
            $table->decimal('total_users_sallary', 20, 4)->default(0)->change();
         
        });
    }

    public function down(): void
    {
        Schema::table('bd_sallaries', function (Blueprint $table) {
            $table->float('sallary')->default(0)->change();
            $table->float('cut_amount')->default(0)->change();
            $table->float('total_agency_sallary')->default(0)->change();
            $table->float('total_users_sallary')->default(0)->change();
         
        });
    }
};
