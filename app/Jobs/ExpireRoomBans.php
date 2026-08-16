<?php

namespace App\Jobs;

use App\Repositories\RoomBlacklistRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireRoomBans implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * This job marks expired bans as inactive
     */
    public function handle(): void
    {
        $blacklistRepo = app(RoomBlacklistRepository::class);

        try {
            $expiredCount = $blacklistRepo->markExpiredBansAsInactive();

            if ($expiredCount > 0) {
                Log::info("Expired room bans job: Marked {$expiredCount} bans as inactive");
            }
        } catch (\Exception $e) {
            Log::error("Error expiring room bans: " . $e->getMessage());
            throw $e;
        }
    }
}
