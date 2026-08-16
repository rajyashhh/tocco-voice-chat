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
        Schema::create('home_carousel_displays', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('home_carousel_id');
            $table->foreign('home_carousel_id')
                  ->references('id')
                  ->on('home_carousels')
                  ->onDelete('cascade');
            $table->enum('display_type', ['discover','home_top','home_middle','live','country']);
            $table->timestamp('end_at')->nullable();
            $table->integer('duration')->default(0);
            $table->enum('duration_unit', ['hours','days','months'])->default('hours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_carousel_displays');
    }
};
