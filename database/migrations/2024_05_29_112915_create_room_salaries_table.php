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
        Schema::create('room_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('room_id')->index();
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->integer("salary")->default(0);
            $table->integer("cut_amount")->default(0);
            $table->integer('month')->default (0);
            $table->integer('year')->default (0);
            $table->boolean('is_paid')->default (0);
            $table->string('diamond')->default ("0/0");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_salaries');
    }
};
