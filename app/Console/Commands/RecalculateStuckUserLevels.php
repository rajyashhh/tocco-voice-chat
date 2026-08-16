<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Illuminate\Support\Facades\DB;

class RecalculateStuckUserLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'levels:recalculate-stuck {--level=22 : The level to check} {--dry-run : Preview changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate levels for users stuck at specific level due to duplicate exp values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetLevel = (int) $this->option('level');
        $isDryRun = $this->option('dry-run');

        $this->info("Looking for users stuck at level {$targetLevel}...");

        // Get the exp threshold for the target level
        $levelExp = DB::table('vips')
            ->where('type', 2)
            ->where('level', $targetLevel)
            ->value('exp');

        if (!$levelExp) {
            $this->error("Level {$targetLevel} not found in vips table");
            return 1;
        }

        $expPercentage = config('exp_percentages.exp_sender_percentage', 0.2);

        // Find users stuck at target level who have enough diamonds for higher level
        $stuckUsers = User::where('sender_level', $targetLevel)
            ->whereRaw('(total_sender_diamonds * ?) > ?', [$expPercentage, $levelExp])
            ->get();

        if ($stuckUsers->isEmpty()) {
            $this->info('No stuck users found!');
            return 0;
        }

        $this->info("Found {$stuckUsers->count()} stuck users");

        $progressBar = $this->output->createProgressBar($stuckUsers->count());
        $progressBar->start();

        $upgradeLevelService = new UpgradeLevelServices();
        $updated = 0;

        foreach ($stuckUsers as $user) {
            if ($isDryRun) {
                $this->newLine();
                $this->line("Would recalculate: User ID {$user->id} - Current Level {$user->sender_level} - Diamonds: {$user->total_sender_diamonds}");
            } else {
                try {
                    $oldLevel = $user->sender_level;
                    $upgradeLevelService->checkUserLevelUpgrated($user);
                    $user->refresh();

                    if ($user->sender_level > $oldLevel) {
                        $updated++;
                        $this->newLine();
                        $this->info("User ID {$user->id}: Level {$oldLevel} → {$user->sender_level}");
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Error updating user {$user->id}: {$e->getMessage()}");
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->warn('This was a dry run. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info("Successfully updated {$updated} users!");
        }

        return 0;
    }
}
