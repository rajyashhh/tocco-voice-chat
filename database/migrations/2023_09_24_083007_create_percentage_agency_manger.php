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
        Schema::create('percentage_agency_manger', function (Blueprint $table) {
            $table->id();
            $table->integer('agency_manger_id')->unsigned()->index()->nullable();
            $table->integer('percentage')->default(10);    // min: 10, max: 100
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percentage_agency_manger');
    }
};
