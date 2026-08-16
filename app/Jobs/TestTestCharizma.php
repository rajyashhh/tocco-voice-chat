<?php

namespace App\Jobs;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Classes\Gifts\PKWork;

class TestTestCharizma implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes max
    public $tries = 3; // Maximum 3 attempts
    public $backoff = [30, 60]; // Retry after 30s, then 60s

    private array $data;
    private string $jobType; // Store type instead of interface

    /**
     * Create a new job instance.
     */
    public function __construct($data, $roomJob)
    {
        $this->data = is_array($data) ? $data : [];
        // Charisma is now fully client-side (owner decision): the only room job
        // that still rides this pipe is PK. $roomJob is always a PKWork.
        $this->jobType = 'pk';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // PK is the only room job on this pipe (charisma is client-side now).
            $roomJob = new PKWork();

            if (!is_array($this->data) || empty($this->data)) {
                Log::warning('TestTestCharizma: Empty or invalid data', [
                    'job_type' => $this->jobType,
                    'data' => $this->data
                ]);
                return;
            }

            foreach ($this->data as $value) {
                try {
                    [$data, $roomId, $userId] = $roomJob->getVariables($value);

                    $json = $roomJob->sendToStream($data, $roomId, $userId ?? 0);

                    // Publish the realtime frame synchronously inside this (already-queued)
                    // worker — same single-hop pattern as the proven gift path
                    // (SendRoomDataJob). Avoids the extra `default`-queue hop that
                    // Common::sendToStream3 -> PushStreamDataJob introduces, which was
                    // silently dropping live charisma frames when that worker lagged.
                    Common::streamSendData((string) $roomId, json_encode([
                        'Action'         => 'SendCustomCommand',
                        'MessageContent' => $json,
                        'FromUserId'     => $userId ?? 0,
                    ]));
                } catch (\Throwable $e) {
                    Log::error('TestTestCharizma: Failed to process item', [
                        'job_type' => $this->jobType,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue processing other items
                    continue;
                }
            }
        } catch (\Throwable $e) {
            Log::error('TestTestCharizma: Job failed completely', [
                'job_type' => $this->jobType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // If we've tried 3 times, don't retry anymore
            if ($this->attempts() >= $this->tries) {
                Log::critical('TestTestCharizma: Job permanently failed after max attempts', [
                    'job_type' => $this->jobType,
                    'attempts' => $this->attempts()
                ]);
                return; // Don't throw - prevent infinite retries
            }

            throw $e; // Retry
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('TestTestCharizma: Job marked as failed', [
            'job_type' => $this->jobType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
