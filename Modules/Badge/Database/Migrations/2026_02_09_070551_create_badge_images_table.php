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
        Schema::create('badge_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('badge_id');
            $table->string('language')->nullable();
            $table->string('image')->nullable();
            $table->string('show_image')->nullable();
            $table->string('image_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_images');
    }
};
