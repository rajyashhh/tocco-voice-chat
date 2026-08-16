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
        if (!Schema::hasColumn('user_achievement_levels', 'custom_achievement_id')) {
            Schema::table('user_achievement_levels', function (Blueprint $table) {
                $table->unsignedBigInteger('custom_achievement_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_achievement_levels', function (Blueprint $table) {
            $table->dropColumn('custom_achievement_id');
        });
    }
};
