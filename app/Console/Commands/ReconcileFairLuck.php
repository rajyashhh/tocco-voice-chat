<?php

namespace App\Console\Commands;

use App\Models\FairLuckWallet;
use App\Services\FairLuck\V7\PoolManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * fairluck:reconcile
 *
 * Verifies the money invariant of the algorithm/luck vault:
 *
 *     vault == baseline + intake - payout
 *
 * where `baseline` is the vault balance when the cumulative counters started, and
 * `intake`/`payout` are the running sums of every net bet credited and every win
 * paid (both maintained atomically inside the same Lua scripts that move the
 * money). Any non-zero drift means a credit/debit happened outside the scripts (a
 * bug, a manual edit, or data loss) and is logged as a CRITICAL alert.
 */
class ReconcileFairLuck extends Command
{
    protected $signature = 'fairluck:reconcile {--tolerance=0 : Allowed absolute drift before alerting}';

    protected $description = 'Verify the FairLuck vault invariant (vault == baseline + intake - payout) and alert on drift.';

    public function handle()
    {
        $redis = FairLuckWallet::vaultRedis();

        $vault    = (int) ($redis->get(PoolManager::KEY_VAULT) ?? 0);
        $baseline = $redis->get(PoolManager::KEY_STAT_BASELINE);
        $intake   = (int) ($redis->get(PoolManager::KEY_STAT_INTAKE) ?? 0);
        $payout   = (int) ($redis->get(PoolManager::KEY_STAT_PAYOUT) ?? 0);

        if ($baseline === null) {
            $this->warn('Reconciliation baseline not initialised yet — run fairluck:sync-wallets first. Skipping.');
            return self::SUCCESS;
        }

        $baseline  = (int) $baseline;
        $expected  = $baseline + $intake - $payout;
        $drift     = $vault - $expected;
        $tolerance = (int) $this->option('tolerance');

        $context = compact('vault', 'baseline', 'intake', 'payout', 'expected', 'drift');

        $this->table(
            ['vault', 'baseline', 'intake', 'payout', 'expected', 'drift'],
            [[$vault, $baseline, $intake, $payout, $expected, $drift]]
        );

        if (abs($drift) > $tolerance) {
            Log::channel('lucky_gift')->critical('FairLuck reconciliation DRIFT detected', $context);
            $this->error("RECONCILIATION FAILED — drift = {$drift} (tolerance {$tolerance})");
            return self::FAILURE;
        }

        $this->info('Reconciliation OK — vault matches intake/payout ledger.');
        return self::SUCCESS;
    }
}
