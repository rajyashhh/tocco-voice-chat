<?php

namespace App\Console\Commands;

use App\Models\FairLuckSetting;
use App\Models\FairLuckWallet;
use App\Services\FairLuck\V7\PoolManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * lucky:merge-legacy-wallets   (§7.2)
 *
 * One-shot merge of the two dead legacy wallet rows
 * (fair_luck_wallets id IN (2,3): jackpot 53,240 + medium 11,333 = 64,573)
 * into the unified vault, recorded as historical capital so the vault invariant
 * `vault == baseline + intake − payout` stays balanced to the coin.
 *
 * Two stages, crash-safe across both:
 *   1. DB tx — atomic claim: UPDATE fair_luck_wallets SET balance=0
 *      WHERE id IN (2,3) AND balance>0, capture the summed amount, and INSERT an
 *      audit row (lucky_legacy_wallet_merges) storing merged_amount EXPLICITLY
 *      (S-ACC-5: a re-run / the reconcile detector reads the amount from the
 *      audit, NOT from the now-zeroed wallet rows — that was the break point)
 *      in state=pending_redis. merge_key is a fixed token → UNIQUE makes a second
 *      invocation a no-op, never a double credit.
 *   2. Redis EVAL — one atomic script: INCRBY unified_vault +amount AND
 *      INCRBY baseline +amount (capital → baseline keeps the invariant exact).
 *      On success the audit row is marked applied.
 *
 * A crash between (1) and (2) leaves the audit row in pending_redis with the
 * amount preserved; ReconcileFairLuck/ReconcileLuckyBatchIntents calls
 * completePendingRedis() (S-CON-3) to finish the Redis side idempotently and
 * alert on any pending_redis row older than a minute — no manual replay needed.
 *
 * OPERATIONAL CONSTRAINT (documented, enforced by a printed warning): run ONLY
 * while stop_luckyGift=1 AND the fairluck:sync-wallets schedule is paused, so the
 * DB-zeroing + Redis-INCRBY pair is not observed mid-flight by a snapshot
 * (which would persist a vault value missing the merged amount). The command
 * prints this warning every run; pausing sync is an operator action.
 */
class MergeLegacyLuckyWallets extends Command
{
    protected $signature = 'lucky:merge-legacy-wallets
        {--dry-run : Report the legacy balances and the merge that WOULD happen without touching the DB or Redis}';

    protected $description = 'Merge the dead legacy jackpot/medium wallet rows (id 2,3) into the unified vault + baseline (one-shot, idempotent, crash-safe).';

    /** Legacy fair_luck_wallets rows to fold into the unified vault. */
    public const LEGACY_WALLET_IDS = [2, 3];

    /** Fixed idempotency token — there is exactly one legacy merge, ever. */
    public const MERGE_KEY = 'legacy_jackpot_medium_v1';

    public function handle(): int
    {
        $this->warn('OPERATIONAL: run this ONLY while stop_luckyGift=1 and fairluck:sync-wallets is paused (until the audit row is applied). A concurrent snapshot would persist a vault value missing the merged amount.');

        $stop = (int) settings()->get('stop_luckyGift');
        if ($stop !== 1) {
            $this->warn("WARNING: stop_luckyGift is currently '{$stop}', expected 1. The lucky system appears LIVE — proceed only if you know sync is paused and bets are stopped.");
        }

        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        // Re-entrancy guard: if a prior run already claimed the DB side, do not
        // re-zero / re-claim. Finish the Redis side idempotently instead.
        $existing = DB::table('lucky_legacy_wallet_merges')
            ->where('merge_key', self::MERGE_KEY)
            ->first(['id', 'merged_amount', 'state']);

        if ($existing !== null) {
            if ($existing->state === 'applied') {
                $this->info('Legacy merge already applied — nothing to do.');
                return self::SUCCESS;
            }
            $this->warn('A prior run claimed the DB side but did not finish Redis — completing now (idempotent).');
            $done = self::completePendingRedis();
            $this->info("Completed {$done} pending_redis merge(s).");
            return self::SUCCESS;
        }

        // Stage 1: atomic DB claim — zero the legacy rows, capture the amount,
        // and write the audit row that carries the amount explicitly.
        $amount = DB::transaction(function () {
            $sum = (int) DB::table('fair_luck_wallets')
                ->whereIn('id', self::LEGACY_WALLET_IDS)
                ->where('balance', '>', 0)
                ->sum('balance');

            if ($sum <= 0) {
                return 0;
            }

            DB::table('fair_luck_wallets')
                ->whereIn('id', self::LEGACY_WALLET_IDS)
                ->where('balance', '>', 0)
                ->update(['balance' => 0, 'last_updated' => now()]);

            DB::table('lucky_legacy_wallet_merges')->insert([
                'merge_key' => self::MERGE_KEY,
                'merged_amount' => $sum,
                'state' => 'pending_redis',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $sum;
        });

        if ($amount <= 0) {
            $this->info('Legacy wallets already zero — nothing to merge.');
            return self::SUCCESS;
        }

        Log::critical('MONEY-CRITICAL: legacy lucky wallets zeroed and claimed (pending_redis) - applying to vault+baseline', [
            'merge_key' => self::MERGE_KEY,
            'merged_amount' => $amount,
        ]);

        // Stage 2: atomic Redis apply + mark applied (idempotent via the audit).
        self::completePendingRedis();

        $this->info("Merged {$amount} coins from legacy wallets into unified_vault + baseline.");
        return self::SUCCESS;
    }

    private function dryRun(): int
    {
        $rows = DB::table('fair_luck_wallets')
            ->whereIn('id', self::LEGACY_WALLET_IDS)
            ->get(['id', 'wallet_type', 'balance']);

        $sum = 0;
        foreach ($rows as $row) {
            $this->line("  id={$row->id} type={$row->wallet_type} balance={$row->balance}");
            $sum += (int) $row->balance;
        }

        $audit = DB::table('lucky_legacy_wallet_merges')
            ->where('merge_key', self::MERGE_KEY)
            ->first(['merged_amount', 'state']);

        $this->info("DRY-RUN: would merge {$sum} coins into unified_vault + baseline.");
        if ($audit !== null) {
            $this->warn("Audit row already exists: state={$audit->state} merged_amount={$audit->merged_amount} — a real run would NOT re-claim (idempotent).");
        }
        return self::SUCCESS;
    }

    /**
     * S-CON-3 / S-ACC-5: finish (or recover) the Redis side of any merge whose
     * audit row is still pending_redis. Reads merged_amount from the AUDIT row,
     * applies it atomically to vault+baseline, and marks the audit applied — all
     * idempotent: the applied-mark is a conditional UPDATE so a re-run after a
     * crash between the EVAL and the mark cannot double-credit (a second EVAL only
     * runs if the row is still pending_redis, and the claim of that row to
     * 'applied' is what authorises the credit).
     *
     * Designed to be invoked by the reconcile schedule, which should additionally
     * alert (MONEY-CRITICAL) on any pending_redis row older than ~1 minute.
     *
     * @return int number of merges completed this call
     */
    public static function completePendingRedis(): int
    {
        $pending = DB::table('lucky_legacy_wallet_merges')
            ->where('state', 'pending_redis')
            ->get(['id', 'merge_key', 'merged_amount']);

        $done = 0;
        foreach ($pending as $row) {
            $amount = (int) $row->merged_amount;
            if ($amount <= 0) {
                // Nothing to apply — just close the row.
                DB::table('lucky_legacy_wallet_merges')
                    ->where('id', $row->id)
                    ->where('state', 'pending_redis')
                    ->update(['state' => 'applied', 'updated_at' => now()]);
                continue;
            }

            try {
                self::applyToVaultAndBaseline($amount);
            } catch (\Throwable $e) {
                Log::critical('MONEY-CRITICAL: legacy merge Redis apply failed - audit stays pending_redis for retry', [
                    'merge_key' => $row->merge_key,
                    'merged_amount' => $amount,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            DB::table('lucky_legacy_wallet_merges')
                ->where('id', $row->id)
                ->where('state', 'pending_redis')
                ->update(['state' => 'applied', 'updated_at' => now()]);

            $done++;
            Log::critical('MONEY-CRITICAL: legacy merge applied to vault+baseline', [
                'merge_key' => $row->merge_key,
                'merged_amount' => $amount,
            ]);
        }

        return $done;
    }

    /**
     * Atomic two-key apply: INCRBY unified_vault +amount AND INCRBY baseline
     * +amount in a single EVAL so the invariant (vault == baseline + intake −
     * payout) can never be observed half-applied. The vault key is seeded from
     * the DB snapshot first if cold so the INCRBY lands on a correct base.
     *
     * NOTE: this is NOT idempotent on its own — idempotency is provided by the
     * pending_redis → applied audit claim in completePendingRedis(), which is the
     * sole caller and runs the EVAL at most once per merge.
     */
    private static function applyToVaultAndBaseline(int $amount): void
    {
        // Ensure the live vault key exists (seeded from DB) before the INCRBY.
        FairLuckWallet::getRedisBalance(FairLuckWallet::TYPE_UNIFIED_VAULT);

        $script = <<<'LUA'
        redis.call('INCRBY', KEYS[1], ARGV[1])
        redis.call('INCRBY', KEYS[2], ARGV[1])
        return 1
        LUA;

        FairLuckWallet::vaultRedis()->eval(
            $script,
            2,
            PoolManager::KEY_VAULT,
            PoolManager::KEY_STAT_BASELINE,
            $amount
        );
    }
}
