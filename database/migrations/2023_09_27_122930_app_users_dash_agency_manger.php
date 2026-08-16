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
        Schema::create('agency_manger_app_dash', function (Blueprint $table) {
            $table->id();
            $table->integer('dash_id')->default(0);
            $table->integer('app_id')->default(0);    // min: 10, max: 100
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_manger_app_dash');

    }
};
