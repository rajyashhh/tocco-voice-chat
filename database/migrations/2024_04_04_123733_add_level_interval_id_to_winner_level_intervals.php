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
        Schema::table('winner_level_intervals', function (Blueprint $table) {
            $table->bigInteger('level_interval_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('winner_level_intervals', function (Blueprint $table) {
            $table->dropColumn('level_interval_id');
        });
    }
};
