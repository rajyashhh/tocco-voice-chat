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
        Schema::create('agency_manger_pulling_out', function (Blueprint $table) {
            $table->id();
            $table->integer('agency_manger_id')->unsigned()->index()->nullable();
            $table->integer('amount')->default(0);    // min: 10, max: 100
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_manger_pulling_out');
    }
};
