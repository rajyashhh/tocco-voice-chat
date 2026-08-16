<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateOldCoinGameUsers extends Command
{
    protected $signature = 'aggregate:old-coin-game-users {--start=} {--end=}';
    protected $description = 'Aggregate old coin game users data in batches (by month)';

    public function handle()
    {
        $startDate = $this->option('start') 
            ? Carbon::parse($this->option('start'))->startOfMonth()
            : \DB::table('coin_game_users')->min('created_at');

        $endDate = $this->option('end') 
            ? Carbon::parse($this->option('end'))->endOfMonth()
            : Carbon::now();

        $start = Carbon::parse($startDate)->startOfMonth();
        $end   = Carbon::parse($endDate)->endOfMonth();

        $this->info("Aggregating data from {$start->toDateString()} to {$end->toDateString()}");

        while ($start <= $end) {
            $batchStart = $start->copy();
            $batchEnd   = $start->copy()->endOfMonth();

            $this->aggregateMonth($batchStart, $batchEnd);

            $start->addMonth();
        }

        $this->info("✅ All data aggregated successfully.");
    }

    protected function aggregateMonth(Carbon $from, Carbon $to)
    {
        $this->info("Processing: {$from->toDateString()} → {$to->toDateString()}");

        $start = $from->format('Y-m-d 00:00:00');
        $end   = $to->format('Y-m-d 23:59:59');

        DB::statement("
            INSERT INTO coin_game_users_daily_aggregated (
                user_id, game_id, date,
                total_played, total_loss, total_win, app_profit,
                created_at, updated_at
            )
            SELECT
                c.user_id,
                COALESCE(c.game_id, 0) as game_id,
                DATE(c.created_at) as date,
                SUM(c.coins) as total_played,
                SUM(CASE WHEN c.type = 0 THEN c.coins ELSE 0 END) as total_loss,
                SUM(CASE WHEN c.type = 1 THEN c.coins ELSE 0 END) as total_win,
                (SUM(CASE WHEN c.type = 0 THEN c.coins ELSE 0 END) -
                 SUM(CASE WHEN c.type = 1 THEN c.coins ELSE 0 END)) as app_profit,
                NOW(), NOW()
            FROM (
                SELECT user_id, game_id, coins, type, created_at
                FROM coin_game_users
                WHERE created_at BETWEEN '$start' AND '$end'
                
                UNION ALL
                
                SELECT user_id, game_id, coins, type, created_at
                FROM coin_game_users_archive
                WHERE created_at BETWEEN '$start' AND '$end'
            ) c
            GROUP BY c.user_id, COALESCE(c.game_id, 0), DATE(c.created_at)
            ON DUPLICATE KEY UPDATE
                total_played = VALUES(total_played),
                total_loss = VALUES(total_loss),
                total_win = VALUES(total_win),
                app_profit = VALUES(app_profit),
                updated_at = NOW()
        ");

        $this->info("✅ Done for {$from->format('Y-m')}");
    }
}
