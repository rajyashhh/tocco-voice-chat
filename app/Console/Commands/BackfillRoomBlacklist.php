<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomBlacklist;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillRoomBlacklist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room:backfill-blacklist {--chunk=100 : Number of rooms to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill room_blacklist table from room_black column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of room blacklist...');

        $chunkSize = (int) $this->option('chunk');
        $totalRooms = Room::whereNotNull('room_black')
            ->where('room_black', '!=', '')
            ->count();

        if ($totalRooms === 0) {
            $this->info('No rooms with blacklist data found.');
            return 0;
        }

        $this->info("Found {$totalRooms} rooms with blacklist data.");
        $bar = $this->output->createProgressBar($totalRooms);
        $bar->start();

        $processedCount = 0;
        $errorCount = 0;
        $totalBansCreated = 0;

        Room::whereNotNull('room_black')
            ->where('room_black', '!=', '')
            ->chunk($chunkSize, function ($rooms) use ($bar, &$processedCount, &$errorCount, &$totalBansCreated) {
                foreach ($rooms as $room) {
                    try {
                        $bansCreated = $this->backfillRoom($room);
                        $totalBansCreated += $bansCreated;
                        $processedCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->error("\nError processing room {$room->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Backfill complete!");
        $this->info("Successfully processed: {$processedCount} rooms");
        $this->info("Total bans created: {$totalBansCreated}");
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount} rooms");
        }

        // Verify data integrity
        $this->info("\nVerifying data integrity...");
        $this->verifyDataIntegrity();

        return 0;
    }

    /**
     * Backfill blacklist for a single room
     *
     * @param Room $room
     * @return int Number of bans created
     */
    private function backfillRoom(Room $room): int
    {
        $blacklistEntries = array_filter(explode(',', $room->room_black ?? ''));
        $bansCreated = 0;

        foreach ($blacklistEntries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) {
                continue;
            }

            // Parse format: user_id#timestamp or user_id#timestamp#duration
            $parts = explode('#', $entry);
            if (count($parts) < 2) {
                $this->warn("\nInvalid blacklist format in room {$room->id}: {$entry}");
                continue;
            }

            $userId = (int) $parts[0];
            $bannedTimestamp = (int) $parts[1];
            $duration = isset($parts[2]) ? (int) $parts[2] : null;

            if ($userId <= 0 || $bannedTimestamp <= 0) {
                continue;
            }

            try {
                $bannedAt = Carbon::createFromTimestamp($bannedTimestamp);
                $expiresAt = $duration ? Carbon::createFromTimestamp($bannedTimestamp + $duration) : null;

                // Check if ban is still active
                $isActive = true;
                if ($expiresAt && $expiresAt->isPast()) {
                    $isActive = false;
                }

                RoomBlacklist::firstOrCreate([
                    'room_id' => $room->id,
                    'user_id' => $userId,
                    'banned_at' => $bannedAt,
                ], [
                    'duration_seconds' => $duration,
                    'expires_at' => $expiresAt,
                    'is_active' => $isActive,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $bansCreated++;
            } catch (\Exception $e) {
                $this->warn("\nError creating ban for user {$userId} in room {$room->id}: " . $e->getMessage());
            }
        }

        return $bansCreated;
    }

    /**
     * Verify data integrity after backfill
     *
     * @return void
     */
    private function verifyDataIntegrity(): void
    {
        $discrepancies = 0;

        Room::whereNotNull('room_black')
            ->where('room_black', '!=', '')
            ->chunk(100, function ($rooms) use (&$discrepancies) {
                foreach ($rooms as $room) {
                    $legacyBans = array_filter(explode(',', $room->room_black ?? ''));
                    $legacyUserIds = [];

                    foreach ($legacyBans as $entry) {
                        $parts = explode('#', $entry);
                        if (count($parts) >= 2) {
                            $legacyUserIds[] = (int) $parts[0];
                        }
                    }

                    sort($legacyUserIds);

                    $newUserIds = RoomBlacklist::where('room_id', $room->id)
                        ->pluck('user_id')
                        ->toArray();
                    sort($newUserIds);

                    if ($legacyUserIds !== $newUserIds) {
                        $this->warn("Discrepancy in room {$room->id}:");
                        $this->line("  Legacy user IDs: " . implode(',', $legacyUserIds));
                        $this->line("  New user IDs: " . implode(',', $newUserIds));
                        $discrepancies++;
                    }
                }
            });

        if ($discrepancies === 0) {
            $this->info("✓ Data integrity verified! No discrepancies found.");
        } else {
            $this->warn("✗ Found {$discrepancies} discrepancies. Please review the output above.");
        }
    }
}
