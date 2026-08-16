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
            if (!Schema::hasColumn('user_sallaries', 'is_finished')) {
                $table->boolean('is_finished')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            if (Schema::hasColumn('user_sallaries', 'is_finished')) {
                Schema::table('user_sallaries', function (Blueprint $table) {
                    $table->dropColumn('is_finished');
                });
            }
        });
    }
};
