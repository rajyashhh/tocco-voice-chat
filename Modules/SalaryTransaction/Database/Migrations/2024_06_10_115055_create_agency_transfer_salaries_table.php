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
        Schema::create('agency_transfer_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_id')->default (0);
            $table->float('salary')->default (0);
            $table->float('cut_amount')->default (0);
            $table->integer('month')->default (0);
            $table->integer('year')->default (0);
            $table->integer('pending_usd')->default (0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_transfer_salaries');
    }
};
