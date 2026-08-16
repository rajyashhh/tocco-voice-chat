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
        Schema::create('charisma_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('level')->unique();
            $table->unsignedBigInteger('points')->unique();
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charisma_levels');
    }
};
