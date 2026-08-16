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
        Schema::create('bd_salaries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bd_id');
            $table->decimal('salary', 12, 2)->default(0);
            $table->decimal('cut_amount', 12, 2)->default(0);
            $table->bigInteger('month'); 
            $table->bigInteger('year'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_salaries');
    }
};
