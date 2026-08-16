<?php

namespace App\Console\Commands;

use App\Repositories\RoomBlacklistRepository;
use Illuminate\Console\Command;

class CleanupExpiredRoomBans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room:cleanup-expired-bans {--days=30 : Number of days old to consider for cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old expired room bans (older than specified days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up expired bans older than {$days} days...");

        $blacklistRepo = app(RoomBlacklistRepository::class);

        try {
            $deletedCount = $blacklistRepo->cleanupOldExpiredBans($days);

            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} old expired bans.");
            } else {
                $this->info("No old expired bans found to clean up.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error cleaning up expired bans: " . $e->getMessage());
            return 1;
        }
    }
}
