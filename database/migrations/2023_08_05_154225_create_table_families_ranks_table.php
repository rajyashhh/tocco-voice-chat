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
        Schema::create('family_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('family_id')->index();
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
            $table->integer('day');
            $table->integer('month');
            $table->integer('year');
            $table->unsignedBigInteger('coins')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_ranks');
    }
};
