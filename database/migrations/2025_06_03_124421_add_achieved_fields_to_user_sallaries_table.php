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
            $table->integer('achieved_diamond')->default(0)->after('diamond');
            $table->integer('achieved_days')->default(0)->after('achieved_diamond');
            $table->integer('achieved_hours')->default(0)->after('achieved_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            $table->dropColumn(['achieved_diamond', 'achieved_days', 'achieved_hours']);
        });
    }
};
