<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Fix: Convert hours column from VARCHAR to DECIMAL.
 *
 * Problem: The `hours` column is VARCHAR(255) but is used in SUM(hours).
 * MySQL must perform implicit casting (string → number) for every row
 * on every query, adding significant overhead.
 *
 * Also fixes: `days` column (VARCHAR → INT), `end_time` (VARCHAR → INT).
 *
 * Solution: Convert to proper numeric types after validating data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Check for non-numeric values in hours column
        $badHours = DB::select("SELECT id, hours FROM live_times WHERE hours IS NOT NULL AND hours REGEXP '[^0-9.]' LIMIT 10");

        if (count($badHours) > 0) {
            Log::warning('Non-numeric values found in live_times.hours column. Cleaning before migration.', [
                'sample' => collect($badHours)->map(fn($r) => ['id' => $r->id, 'hours' => $r->hours])->toArray()
            ]);

            // Set non-numeric values to NULL to prevent conversion errors
            DB::statement("UPDATE live_times SET hours = NULL WHERE hours IS NOT NULL AND hours REGEXP '[^0-9.]'");
        }

        // Step 2: Check for non-numeric values in days column
        $badDays = DB::select("SELECT id, days FROM live_times WHERE days IS NOT NULL AND days REGEXP '[^0-9]' LIMIT 10");

        if (count($badDays) > 0) {
            Log::warning('Non-numeric values found in live_times.days column. Cleaning before migration.', [
                'sample' => collect($badDays)->map(fn($r) => ['id' => $r->id, 'days' => $r->days])->toArray()
            ]);

            DB::statement("UPDATE live_times SET days = NULL WHERE days IS NOT NULL AND days REGEXP '[^0-9]'");
        }

        // Step 3: Convert column types
        Schema::table('live_times', function (Blueprint $table) {
            $table->decimal('hours', 10, 2)->nullable()->change();
            $table->integer('days')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('live_times', function (Blueprint $table) {
            $table->string('hours')->nullable()->change();
            $table->string('days')->nullable()->change();
        });
    }
};
