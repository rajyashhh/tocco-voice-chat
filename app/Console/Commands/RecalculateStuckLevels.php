<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Modules\Public\Http\Services\UpgradeLevelServices;

/**
 * Recalculate sender levels for users stuck at level 22 or 31+
 * 
 * Usage: php artisan app:recalculate-stuck-levels
 */
class RecalculateStuckLevels extends Command
{
    protected $signature = 'app:recalculate-stuck-levels {--dry-run}';
    protected $description = 'Recalculate sender levels for users stuck due to duplicate vip exp values';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('═════════════════════════════════════════════════════════════');
        $this->info('Recalculate Stuck Levels — Bug Fix');
        $this->info('═════════════════════════════════════════════════════════════');
        $this->newLine();

        $stuckAt22 = User::where('sender_level', 22)
            ->where('total_diamond_send', '>', 83100000)
            ->pluck('id', 'total_diamond_send');

        $this->info("Found " . count($stuckAt22) . " users stuck at level 22");

        $stuckAt31Plus = User::where('sender_level', '>=', 31)
            ->where('total_diamond_send', '>', 208620000)
            ->pluck('id', 'total_diamond_send');

        $this->info("Found " . count($stuckAt31Plus) . " users stuck at levels 31–35");
        $this->newLine();

        if (count($stuckAt22) === 0 && count($stuckAt31Plus) === 0) {
            $this->line('✓ No stuck users found');
            return 0;
        }

        $totalStuck = count($stuckAt22) + count($stuckAt31Plus);
        $this->line("Total stuck users: {$totalStuck}");

        if ($dryRun) {
            $this->warn('(DRY RUN — no changes will be made)');
            $this->newLine();
            return 0;
        }

        $this->line('Recalculating levels...');
        $progressBar = $this->output->createProgressBar($totalStuck);
        $progressBar->start();

        $upgraded = 0;
        $service  = new UpgradeLevelServices();

        // Process stuck at 22
        foreach ($stuckAt22 as $userId => $diamonds) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $oldLevel = $user->sender_level;
                    $service->checkUserLevelUpgrated($user);
                    // Refresh to get updated level
                    $user->refresh();
                    if ($user->sender_level > $oldLevel) {
                        $upgraded++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing user {$userId}: " . $e->getMessage());
            }
            $progressBar->advance();
        }

        // Process stuck at 31+
        foreach ($stuckAt31Plus as $userId => $diamonds) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $oldLevel = $user->sender_level;
                    $service->checkUserLevelUpgrated($user);
                    // Refresh to get updated level
                    $user->refresh();
                    if ($user->sender_level > $oldLevel) {
                        $upgraded++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing user {$userId}: " . $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info('═════════════════════════════════════════════════════════════');
        $this->line("✓ Successfully upgraded: {$upgraded}/{$totalStuck} users");
        $this->info('═════════════════════════════════════════════════════════════');

        return 0;
    }
}
