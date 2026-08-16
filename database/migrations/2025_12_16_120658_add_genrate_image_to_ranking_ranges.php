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
        if (!Schema::hasColumn('ranking_ranges', 'generate_image')) {
            Schema::table('ranking_ranges', function (Blueprint $table) {
               $table->string('generate_image')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranking_ranges', function (Blueprint $table) {
           $table->dropColumn('generate_image');
        });
    }
};
