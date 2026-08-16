<?php

namespace App\Console\Commands;

use App\Services\GameRankingService;
use Illuminate\Console\Command;

class BackfillGameRankingCommand extends Command
{
    protected $signature = 'game:backfill-ranking {--type=all : daily|weekly|monthly|all}';
    protected $description = 'Backfill Games ranking Redis sorted sets from DB';

    public function handle(GameRankingService $service): void
    {
        $types = $this->option('type') === 'all'
            ? ['daily', 'weekly', 'monthly']
            : [$this->option('type')];

        foreach ($types as $type) {
            $this->info("Backfilling $type ranking...");
            $count = $service->backfillFromDB($type);
            $this->info("  → $count users loaded into Redis");
        }

        $this->info('Done! Rankings are now served from Redis.');
    }
}
