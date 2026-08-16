<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if there are duplicates
        $duplicates = DB::select("
            SELECT type, exp, COUNT(*) as count
            FROM vips
            GROUP BY type, exp
            HAVING COUNT(*) > 1
        ");

        // Only add unique constraint if no duplicates exist
        if (empty($duplicates)) {
            Schema::table('vips', function (Blueprint $table) {
                $table->unique(['type', 'exp'], 'vips_type_exp_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vips', function (Blueprint $table) {
            $table->dropUnique('vips_type_exp_unique');
        });
    }
};
