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
        Schema::create('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bd_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('agency_id');
            $table->decimal('amount', 12, 2);
            $table->decimal('user_sallary', 12, 2);
            $table->decimal('agency_sallary', 12, 2);
            $table->bigInteger('month'); 
            $table->bigInteger('year'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_agency_host_sallaries');
    }
};
