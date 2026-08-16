<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add unique index to monthly_diamond_receives with safety check
     * Merges ALL duplicate records across all months/years
     */
    public function up(): void
    {
        // Check if the unique index already exists
        $indexExists = $this->indexExists('monthly_diamond_receives', 'idx_user_month_year');

        if ($indexExists) {
            $this->log('⚠️ Unique index "idx_user_month_year" already exists - skipping all operations', 'warning');
            return;
        }

        // Step 1: Merge ALL duplicates (not just May 2026)
        $this->log('📊 Step 1: Checking for duplicates in ALL months...');
        $totalMerged = $this->mergeAllDuplicates();

        if ($totalMerged > 0) {
            $this->log("✅ Merged {$totalMerged} duplicate groups across all months");
        } else {
            $this->log('✅ No duplicates found');
        }

        // Step 2: Add unique index
        $this->log('📊 Step 2: Adding unique index...');

        try {
            Schema::table('monthly_diamond_receives', function (Blueprint $table) {
                $table->unique(['user_id', 'month', 'year'], 'idx_user_month_year');
            });

            $this->log('✅ Unique index "idx_user_month_year" added successfully');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->log('❌ ERROR: Still have duplicates after merge!', 'error');
                $this->log('This should not happen - please check logs', 'error');
                throw $e;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the unique index exists before dropping
        $indexExists = $this->indexExists('monthly_diamond_receives', 'idx_user_month_year');

        if ($indexExists) {
            Schema::table('monthly_diamond_receives', function (Blueprint $table) {
                $table->dropUnique('idx_user_month_year');
            });

            $this->log('✅ Unique index "idx_user_month_year" dropped successfully');
        } else {
            $this->log('⚠️ Unique index "idx_user_month_year" does not exist - skipping', 'warning');
        }
    }

    /**
     * Merge ALL duplicate records across all months/years
     *
     * @return int Total number of duplicate groups merged
     */
    private function mergeAllDuplicates(): int
    {
        // Find all duplicates across all months/years
        $duplicates = DB::table('monthly_diamond_receives')
            ->select('user_id', 'month', 'year', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'month', 'year')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return 0;
        }

        $this->log("Found {$duplicates->count()} duplicate groups to merge");

        $mergedCount = 0;

        DB::transaction(function () use ($duplicates, &$mergedCount) {
            foreach ($duplicates as $duplicate) {
                // Merge this specific user/month/year group
                $this->mergeDuplicateGroup($duplicate->user_id, $duplicate->month, $duplicate->year);
                $mergedCount++;

                // Log progress every 100 groups
                if ($mergedCount % 100 === 0) {
                    $this->log("Progress: Merged {$mergedCount} / {$duplicates->count()} groups");
                }
            }
        });

        return $mergedCount;
    }

    /**
     * Merge duplicate records for a specific user/month/year
     *
     * @param int $userId
     * @param int $month
     * @param int $year
     * @return void
     */
    private function mergeDuplicateGroup(int $userId, int $month, int $year): void
    {
        // Get all records for this user/month/year
        $records = DB::table('monthly_diamond_receives')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('id')
            ->get();

        if ($records->count() <= 1) {
            return;
        }

        // Calculate totals
        $totalDiamonds = $records->sum('monthly_diamond_received');
        $latestRecord = $records->sortByDesc('updated_at')->first();
        $oldestCreatedAt = $records->min('created_at');

        // Delete all except the latest
        DB::table('monthly_diamond_receives')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('id', '!=', $latestRecord->id)
            ->delete();

        // Update the remaining record with correct total
        DB::table('monthly_diamond_receives')
            ->where('id', $latestRecord->id)
            ->update([
                'monthly_diamond_received' => $totalDiamonds,
                'created_at' => $oldestCreatedAt,
                'updated_at' => now(),
            ]);
    }

    /**
     * Merge duplicate records for a specific month/year
     *
     * @param int $month
     * @param int $year
     * @return int Number of duplicate groups merged
     */
    private function mergeDuplicatesForMonth(int $month, int $year): int
    {
        // Find all duplicates for this month/year
        $duplicates = DB::table('monthly_diamond_receives')
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(monthly_diamond_received) as total'))
            ->where('month', $month)
            ->where('year', $year)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return 0;
        }

        $mergedCount = 0;

        DB::transaction(function () use ($duplicates, $month, $year, &$mergedCount) {
            foreach ($duplicates as $duplicate) {
                // Get all records for this user/month/year
                $records = DB::table('monthly_diamond_receives')
                    ->where('user_id', $duplicate->user_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->orderBy('id')
                    ->get();

                if ($records->count() <= 1) {
                    continue;
                }

                // Calculate totals
                $totalDiamonds = $records->sum('monthly_diamond_received');
                $latestRecord = $records->sortByDesc('updated_at')->first();
                $oldestCreatedAt = $records->min('created_at');

                // Delete all except the latest
                DB::table('monthly_diamond_receives')
                    ->where('user_id', $duplicate->user_id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('id', '!=', $latestRecord->id)
                    ->delete();

                // Update the remaining record with correct total
                DB::table('monthly_diamond_receives')
                    ->where('id', $latestRecord->id)
                    ->update([
                        'monthly_diamond_received' => $totalDiamonds,
                        'created_at' => $oldestCreatedAt,
                        'updated_at' => now(),
                    ]);

                $mergedCount++;
            }
        });

        return $mergedCount;
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $indexName
     * @return bool
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Log a message (compatible with both CLI and non-CLI environments)
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    private function log(string $message, string $level = 'info'): void
    {
        // Try to output to command if available
        if (property_exists($this, 'command') && $this->command) {
            switch ($level) {
                case 'error':
                    $this->command->error($message);
                    break;
                case 'warning':
                    $this->command->warn($message);
                    break;
                default:
                    $this->command->info($message);
            }
        }

        // Always log to Laravel log for Docker/background environments
        Log::info("[Migration] {$message}");
    }
};
