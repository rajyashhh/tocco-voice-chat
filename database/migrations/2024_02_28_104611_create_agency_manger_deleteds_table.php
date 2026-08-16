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
        Schema::create('agency_manger_deleteds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable();
            $table->unsignedInteger('agency_manger_id');
            $table->string('agencies_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_manger_deleteds');
    }
};
