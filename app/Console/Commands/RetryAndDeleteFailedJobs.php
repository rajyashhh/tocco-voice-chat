<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RetryAndDeleteFailedJobs extends Command
{
    protected $signature = 'queue:retry-safe';
    protected $description = 'Retry failed jobs with error tracking and cleanup, showing job class names';

    public function handle()
    {
        $failedJobs = DB::table('failed_jobs')->get();

        if ($failedJobs->isEmpty()) {
            $this->info('✅ No failed jobs found.');
            return;
        }

        $this->info("🔁 Retrying {$failedJobs->count()} failed jobs...\n");

        $failedAgain = [];

        foreach ($failedJobs as $job) {
            try {
                // Decode payload to get job class name
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? ($payload['job'] ?? 'UnknownJob');

                $this->line("➡️ Retrying Job: {$jobClass}");

                // Retry the job
                Artisan::call('queue:retry', ['id' => $job->id]);

                // Give time for worker to handle it
                sleep(1);

                // Check if still failed
                $stillFailed = DB::table('failed_jobs')->where('id', $job->id)->exists();

                if (! $stillFailed) {
                    // Job succeeded
                    DB::table('failed_jobs')->where('id', $job->id)->delete();
                    $this->info("✅ {$jobClass} succeeded and was removed.");
                } else {
                    $this->warn("⚠️ {$jobClass} failed again, keeping in table.");
                    $failedAgain[] = ['class' => $jobClass, 'status' => 'failed_again'];
                }
            } catch (Throwable $e) {
                // Log the error
                $this->error("❌ Error retrying {$jobClass}: {$e->getMessage()}");

                $failedAgain[] = [
                    'class' => $jobClass,
                    'error' => $e->getMessage(),
                    'time' => now()->toDateTimeString(),
                ];
            }
        }

        // Save failed job info to log file
        if (!empty($failedAgain)) {
            $logFile = storage_path('logs/failed_jobs_retry.log');
            file_put_contents(
                $logFile,
                "[" . now() . "] Retried jobs that failed again:\n" . json_encode($failedAgain, JSON_PRETTY_PRINT) . "\n\n",
                FILE_APPEND
            );

            $this->warn("⚠️ Some jobs failed again. Logged to: {$logFile}");
        }

        $this->info("\n🎯 All jobs processed.");
    }
}
