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
        Schema::create('lucky_gifts', function (Blueprint $table) {
            $table->id();
            $table->integer('gift_id')->unsigned()->index()->nullable();
            $table->foreign('gift_id')->references('id')->on('gifts')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('win_probability')->default(10);    // min: 10, max: 100
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lucky_gifts');
    }
};
