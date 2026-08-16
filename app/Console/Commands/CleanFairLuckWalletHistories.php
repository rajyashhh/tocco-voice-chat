<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanFairLuckWalletHistories extends Command
{
    protected $signature = 'cleanup:fair-luck-wallet-histories
                            {--days=30 : Days to keep}
                            {--chunk=5000 : Rows per delete batch}';

    protected $description = 'Delete fair_luck_wallet_histories older than N days (batch delete to avoid locks)';

    public function handle()
    {
        $days = (int) $this->option('days');
        $chunk = (int) $this->option('chunk');
        $cutoff = Carbon::now()->subDays($days);
        $total = 0;

        $this->info("Deleting fair_luck_wallet_histories older than {$cutoff}...");

        do {
            $deleted = DB::table('fair_luck_wallet_histories')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($chunk)
                ->delete();

            $total += $deleted;

            if ($deleted > 0) {
                $this->info("  Deleted batch: {$deleted} rows (total: {$total})");
                usleep(100000); // 100ms pause between batches
            }
        } while ($deleted > 0);

        $this->info("✅ Done. Total deleted: {$total} rows.");

        return Command::SUCCESS;
    }
}
