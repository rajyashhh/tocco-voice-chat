<?php

namespace App\Console\Commands;

use App\Models\CoreWallet;
use App\Models\FairLuckWallet;
use App\Services\FairLuck\V7\PoolManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFairLuckWallets extends Command
{
    protected $signature = 'fairluck:sync-wallets';

    protected $description = 'Snapshot the FairLuck vault to DB and flush the accrued owner cut to owner_wallet (durability + lock-free owner crediting).';

    public function handle()
    {
        // 1) Durability snapshot of the live unified vault (Redis → DB).
        $this->info('Syncing unified_vault...');
        FairLuckWallet::syncToDatabase(FairLuckWallet::TYPE_UNIFIED_VAULT);

        // 2) Initialise the reconciliation baseline BEFORE flushing, so the
        //    invariant (vault == baseline + intake - payout) is always well-defined.
        $this->ensureBaseline();

        // 3) Flush the owner cut accrued on the hot path (Redis counter) into the
        //    dedicated owner_wallet. CRASH-SAFE ordering: credit MySQL inside a
        //    transaction first, then atomically subtract EXACTLY the flushed amount
        //    from the Redis counter. If the credit fails, the counter is untouched
        //    and the amount rolls into the next run (no owner revenue lost). Using
        //    DECRBY by the exact amount (not SET 0) preserves concurrent accruals.
        $this->flushOwnerAccrual();

        $this->info('FairLuck wallets synced at ' . now()->toDateTimeString());
        return self::SUCCESS;
    }


    private function flushOwnerAccrual(): void
    {
        try {
            $redis = FairLuckWallet::vaultRedis();
            $accrued = (int) ($redis->get(PoolManager::KEY_OWNER_ACCRUAL) ?? 0);

            if ($accrued <= 0) {
                return;
            }

            DB::transaction(function () use ($accrued) {
                CoreWallet::firstOrCreate(['name' => 'owner_wallet'], ['coins' => 0]);
                CoreWallet::where('name', 'owner_wallet')->increment('coins', $accrued);
            });

            // Committed — now remove exactly what we credited. Concurrent accruals
            // that landed after our GET survive (DECRBY, not SET 0).
            $redis->decrby(PoolManager::KEY_OWNER_ACCRUAL, $accrued);

            $this->info("Flushed {$accrued} coins to owner_wallet.");

            // Informational only — the sub-coin remainder lives in the vault and is
            // realized to the owner by the batchSettle EVAL (the ONLY writer). The
            // sweep must NEVER read-modify-write this key (single-writer atomicity).
            $remBps = (int) ($redis->get(PoolManager::KEY_OWNER_FRACTION_BPS) ?? 0);
            $this->info("Owner sub-coin remainder carried: {$remBps} bps-coins (" . ($remBps / 10000) . " coin).");
        } catch (\Throwable $e) {
            // Counter left intact → retried next run. Never lose owner revenue.
            $this->warn('Owner accrual flush failed (will retry next run): ' . $e->getMessage());
        }
    }

    /**
     * Seed the reconciliation baseline ATOMICALLY the first time: baseline = vault,
     * intake = 0, payout = 0 in a single Lua script so no in-flight bet can leave a
     * stale intake/payout counter double-counted against the baseline.
     *
     * Run when the system is quiesced (deploy) for a perfectly clean baseline; if it
     * first runs while traffic flows, at most one in-flight bet may skew it by a
     * single net/payout, which the next clean rebaseline corrects.
     */
    private function ensureBaseline(): void
    {
        try {
            $redis = FairLuckWallet::vaultRedis();
            if ($redis->get(PoolManager::KEY_STAT_BASELINE) !== null) {
                return; // already initialised
            }

            // Make sure the vault key is live (atomic SETNX seed) before snapshotting.
            FairLuckWallet::getRedisBalance(FairLuckWallet::TYPE_UNIFIED_VAULT);

            $script = <<<'LUA'
            local vault = tonumber(redis.call('GET', KEYS[1]) or 0)
            redis.call('SET', KEYS[2], vault)  -- baseline
            redis.call('SET', KEYS[3], 0)      -- intake
            redis.call('SET', KEYS[4], 0)      -- payout
            return vault
            LUA;

            $vault = (int) $redis->eval(
                $script,
                4,
                PoolManager::KEY_VAULT,
                PoolManager::KEY_STAT_BASELINE,
                PoolManager::KEY_STAT_INTAKE,
                PoolManager::KEY_STAT_PAYOUT
            );

            $this->info("Reconciliation baseline initialised at vault={$vault}.");
        } catch (\Throwable $e) {
            $this->warn('Baseline init failed: ' . $e->getMessage());
        }
    }
}
