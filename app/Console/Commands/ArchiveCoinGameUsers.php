<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveCoinGameUsers extends Command
{
    protected $signature = 'coin_game:archive {date?}';
    protected $description = 'Archive coin_game_users to coin_game_users_archive day by day';

    public function handle()
    {
        $this->info('Starting daily archive process...');

        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : Carbon::yesterday();

        $ymd = (int) $date->format('Ymd');
        $partitionName = 'p' . $ymd;

        $partitions = DB::select("
            SELECT PARTITION_NAME, PARTITION_DESCRIPTION
            FROM INFORMATION_SCHEMA.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'coin_game_users_archive'
        ");

        $partitionExists = collect($partitions)->pluck('PARTITION_NAME')->contains($partitionName);

        if (!$partitionExists) {
            $lastPartition = collect($partitions)
                ->filter(fn($p) => $p->PARTITION_NAME !== 'pMax')
                ->sortBy('PARTITION_DESCRIPTION')
                ->last();

            $newPartitionValue = $ymd;
            if ($lastPartition && $lastPartition->PARTITION_DESCRIPTION >= $newPartitionValue) {
                $newPartitionValue = $lastPartition->PARTITION_DESCRIPTION + 1;
            }

            try {
                DB::statement("
                    ALTER TABLE coin_game_users_archive
                    REORGANIZE PARTITION pMax INTO (
                        PARTITION {$partitionName} VALUES LESS THAN ({$newPartitionValue}),
                        PARTITION pMax VALUES LESS THAN MAXVALUE
                    )
                ");
                $this->info("Partition {$partitionName} added successfully.");
            } catch (\Exception $e) {
                $this->error("Failed to add partition {$partitionName}: " . $e->getMessage());
                return;
            }
        } else {
            $this->info("Partition {$partitionName} already exists.");
        }

        $lastId = DB::table('coin_game_users')
            ->where('created_at', '>=', $date->toDateString())
            ->where('created_at', '<', $date->copy()->addDay()->toDateString())
            ->max('id');

        if (!$lastId) {
            $this->info("No records found for {$date->toDateString()}, nothing to archive.");
            return;
        }

        $dateStr = $date->toDateString();
        $nextDateStr = $date->copy()->addDay()->toDateString();

        DB::beginTransaction();
        try {
            DB::statement("
                INSERT INTO coin_game_users_archive
                (id, user_id, coins, type, game_id, round_id, order_id, app_profit_coins, created_at, updated_at, created_ym)
                SELECT
                    id, user_id, coins, type, game_id, round_id, order_id, app_profit_coins, created_at, updated_at,
                    YEAR(created_at)*10000 + MONTH(created_at)*100 + DAY(created_at)
                FROM coin_game_users
                WHERE id <= {$lastId}
                  AND created_at >= '{$dateStr}' AND created_at < '{$nextDateStr}'
            ");

            DB::statement("
                DELETE FROM coin_game_users
                WHERE id <= {$lastId}
                  AND created_at >= '{$dateStr}' AND created_at < '{$nextDateStr}'
            ");

            DB::commit();
            $this->info("Archive for {$date->toDateString()} completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Archive failed: ' . $e->getMessage());
        }
    }
}
