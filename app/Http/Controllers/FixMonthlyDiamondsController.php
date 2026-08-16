<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixMonthlyDiamondsController extends Controller
{
    /**
     * Fix duplicate monthly diamond receives records
     * This route is idempotent - safe to run multiple times
     */
    public function fix(Request $request)
    {
        $startTime = microtime(true);
        $results = [
            'status' => 'success',
            'steps' => [],
            'stats' => []
        ];

        try {
            DB::beginTransaction();

            // Step 1: Check if unique index already exists
            $results['steps'][] = '1️⃣ Checking if unique index exists...';
            $indexExists = $this->checkUniqueIndexExists();

            if ($indexExists) {
                $results['steps'][] = '✅ Unique index already exists - skipping duplicate fix';
                $results['stats']['index_exists'] = true;
                DB::commit();

                $results['execution_time'] = round(microtime(true) - $startTime, 2) . 's';
                return response()->json($results);
            }

            $results['steps'][] = '⚠️ Unique index NOT found - proceeding with fix';
            $results['stats']['index_exists'] = false;

            // Step 2: Find duplicates
            $results['steps'][] = '2️⃣ Finding duplicate records...';
            $duplicates = DB::table('monthly_diamond_receives')
                ->select('user_id', 'month', 'year', DB::raw('COUNT(*) as count'), DB::raw('SUM(monthly_diamond_received) as total'))
                ->groupBy('user_id', 'month', 'year')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $results['stats']['users_with_duplicates'] = $duplicates->count();
            $results['stats']['total_duplicate_records'] = $duplicates->sum('count') - $duplicates->count();
            $results['stats']['total_diamonds_affected'] = $duplicates->sum('total');

            if ($duplicates->isEmpty()) {
                $results['steps'][] = '✅ No duplicates found!';
            } else {
                $results['steps'][] = "⚠️ Found {$duplicates->count()} users with duplicate records";

                // Step 3: Process each duplicate group
                $results['steps'][] = '3️⃣ Processing duplicates in batches...';
                $processed = 0;
                $batchSize = 100;

                foreach ($duplicates->chunk($batchSize) as $chunk) {
                    foreach ($chunk as $duplicate) {
                        $this->fixDuplicateRecord($duplicate->user_id, $duplicate->month, $duplicate->year);
                        $processed++;
                    }
                }

                $results['steps'][] = "✅ Processed {$processed} duplicate groups";
            }

            // Step 4: Add unique index
            $results['steps'][] = '4️⃣ Adding unique index...';

            try {
                DB::statement('ALTER TABLE monthly_diamond_receives ADD UNIQUE INDEX idx_user_month_year (user_id, month, year)');
                $results['steps'][] = '✅ Unique index added successfully';
                $results['stats']['index_added'] = true;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $results['steps'][] = '❌ ERROR: Still have duplicates after cleanup!';
                    $results['steps'][] = 'Please run again to retry.';
                    throw $e;
                } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    $results['steps'][] = '⚠️ Index already exists (added by another process)';
                    $results['stats']['index_added'] = false;
                } else {
                    throw $e;
                }
            }

            // Step 5: Verify results
            $results['steps'][] = '5️⃣ Verifying results...';
            $remainingDuplicates = DB::table('monthly_diamond_receives')
                ->select('user_id', 'month', 'year', DB::raw('COUNT(*) as count'))
                ->groupBy('user_id', 'month', 'year')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            if ($remainingDuplicates > 0) {
                $results['steps'][] = "❌ WARNING: Still have {$remainingDuplicates} duplicate groups!";
                $results['status'] = 'warning';
            } else {
                $results['steps'][] = '✅ All duplicates resolved!';
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
            $results['steps'][] = '❌ ERROR: ' . $e->getMessage();
        }

        $results['execution_time'] = round(microtime(true) - $startTime, 2) . 's';

        return response()->json($results, $results['status'] === 'error' ? 500 : 200);
    }

    /**
     * Check if unique index exists
     */
    private function checkUniqueIndexExists(): bool
    {
        $indexes = DB::select("SHOW INDEX FROM monthly_diamond_receives WHERE Key_name = 'idx_user_month_year'");
        return !empty($indexes);
    }

    /**
     * Fix a single duplicate record group
     */
    private function fixDuplicateRecord($userId, $month, $year)
    {
        // Get all records for this user/month/year
        $records = DB::table('monthly_diamond_receives')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('id')
            ->get();

        if ($records->count() <= 1) {
            return; // No duplicates
        }

        // Calculate total diamonds and get the latest record
        $totalDiamonds = $records->sum('monthly_diamond_received');
        $latestRecord = $records->sortByDesc('updated_at')->first();
        $oldestCreatedAt = $records->min('created_at');

        // Delete all records except the latest one
        DB::table('monthly_diamond_receives')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('id', '!=', $latestRecord->id)
            ->delete();

        // Update the remaining record with the correct total
        DB::table('monthly_diamond_receives')
            ->where('id', $latestRecord->id)
            ->update([
                'monthly_diamond_received' => $totalDiamonds,
                'created_at' => $oldestCreatedAt,
                'updated_at' => now(),
            ]);
    }

    /**
     * Get statistics about duplicates (read-only)
     */
    public function stats()
    {
        $duplicates = DB::table('monthly_diamond_receives')
            ->select(
                'user_id',
                'month',
                'year',
                DB::raw('COUNT(*) as record_count'),
                DB::raw('SUM(monthly_diamond_received) as total_diamonds'),
                DB::raw('MIN(created_at) as first_created'),
                DB::raw('MAX(updated_at) as last_updated')
            )
            ->groupBy('user_id', 'month', 'year')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('record_count')
            ->limit(100)
            ->get();

        $summary = [
            'total_users_with_duplicates' => $duplicates->count(),
            'total_duplicate_records' => $duplicates->sum('record_count') - $duplicates->count(),
            'total_diamonds_in_duplicates' => $duplicates->sum('total_diamonds'),
            'index_exists' => $this->checkUniqueIndexExists(),
            'top_100_duplicates' => $duplicates
        ];

        return response()->json($summary);
    }
}
