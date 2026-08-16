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
        Schema::table('user_sallaries', function (Blueprint $table) {
            $table->unsignedBigInteger('achieved_diamond')->default(0)->change();
            $table->unsignedBigInteger('remaining_diamond')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            $table->integer('achieved_diamond')->change();
            $table->integer('remaining_diamond')->change();
        });
    }
};
