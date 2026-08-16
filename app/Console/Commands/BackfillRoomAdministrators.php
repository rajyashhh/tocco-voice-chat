<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomAdministrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillRoomAdministrators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'room:backfill-administrators {--chunk=100 : Number of rooms to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill room_administrators table from room_admin column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of room administrators...');

        $chunkSize = (int) $this->option('chunk');
        $totalRooms = Room::whereNotNull('room_admin')
            ->where('room_admin', '!=', '')
            ->count();

        if ($totalRooms === 0) {
            $this->info('No rooms with administrators found.');
            return 0;
        }

        $this->info("Found {$totalRooms} rooms with administrators.");
        $bar = $this->output->createProgressBar($totalRooms);
        $bar->start();

        $processedCount = 0;
        $errorCount = 0;

        Room::whereNotNull('room_admin')
            ->where('room_admin', '!=', '')
            ->chunk($chunkSize, function ($rooms) use ($bar, &$processedCount, &$errorCount) {
                foreach ($rooms as $room) {
                    try {
                        $this->backfillRoom($room);
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
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount} rooms");
        }

        // Verify data integrity
        $this->info("\nVerifying data integrity...");
        $this->verifyDataIntegrity();

        return 0;
    }

    /**
     * Backfill administrators for a single room
     *
     * @param Room $room
     * @return void
     */
    private function backfillRoom(Room $room): void
    {
        $adminIds = array_filter(explode(',', $room->room_admin ?? ''));

        foreach ($adminIds as $adminId) {
            $adminId = trim($adminId);
            if (empty($adminId) || !is_numeric($adminId)) {
                continue;
            }

            RoomAdministrator::firstOrCreate([
                'room_id' => $room->id,
                'user_id' => (int) $adminId
            ], [
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Verify data integrity after backfill
     *
     * @return void
     */
    private function verifyDataIntegrity(): void
    {
        $discrepancies = 0;

        Room::whereNotNull('room_admin')
            ->where('room_admin', '!=', '')
            ->chunk(100, function ($rooms) use (&$discrepancies) {
                foreach ($rooms as $room) {
                    $legacyAdmins = array_filter(explode(',', $room->room_admin ?? ''));
                    $legacyAdmins = array_map('trim', $legacyAdmins);
                    sort($legacyAdmins);

                    $newAdmins = RoomAdministrator::where('room_id', $room->id)
                        ->pluck('user_id')
                        ->map(fn($id) => (string) $id)
                        ->toArray();
                    sort($newAdmins);

                    if ($legacyAdmins !== $newAdmins) {
                        $this->warn("Discrepancy in room {$room->id}:");
                        $this->line("  Legacy: " . implode(',', $legacyAdmins));
                        $this->line("  New: " . implode(',', $newAdmins));
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
