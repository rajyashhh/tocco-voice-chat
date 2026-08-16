<?php

namespace App\Console\Commands;

use App\Models\AdminQueryLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class CleanupAdminQueryLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:cleanup-query-logs {--days=30 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old admin query logs to prevent database bloat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up admin query logs older than {$days} days ({$cutoffDate->toDateString()})...");

        try {
            // Count logs before deletion
            $countBefore = AdminQueryLog::where('created_at', '<', $cutoffDate)->count();

            if ($countBefore === 0) {
                $this->info('No logs to delete.');
                return self::SUCCESS;
            }

            // Delete old logs
            $deleted = AdminQueryLog::where('created_at', '<', $cutoffDate)->delete();

            $this->info("Successfully deleted {$deleted} log entries.");

            // Log the cleanup action
            Log::info('Admin query logs cleanup completed', [
                'days_kept' => $days,
                'cutoff_date' => $cutoffDate->toDateString(),
                'deleted_count' => $deleted,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during cleanup: {$e->getMessage()}");

            Log::error('Admin query logs cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
