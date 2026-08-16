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
        Schema::create('agency_countries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->constrained('agencies')->onDelete ('cascade');
            $table->unsignedInteger ('country_id')->nullable ()->default (0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_countries');
    }
};
