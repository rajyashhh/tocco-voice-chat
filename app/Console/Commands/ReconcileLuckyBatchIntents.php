<?php

namespace App\Console\Commands;

use App\Models\FairLuckWallet;
use App\Services\FairLuck\V7\PoolManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * lucky:reconcile-intents
 *
 * Compensates orphaned lucky-gift BATCH intents (journal rows written atomically
 * with the upfront full-batch debit) left behind by SIGKILL/OOM/timeouts in the
 * debit → batchSettle → post-job window:
 *
 *   status=settled  — the EVAL committed but the post-job never applied the win
 *                     credit. Claim settled→reconciled and credit total_paid.
 *                     (A late job retry will skip the credit: it claims the same
 *                     row conditionally — exactly-once either way.)
 *   status=debited  — crashed before the settled-mark. The commit-proof Redis key
 *                     (stamped INSIDE the batchSettle EVAL) is the authority:
 *                       proof EXISTS → EVAL committed → keep the debit, credit the
 *                                      win (proof value), claim debited→reconciled.
 *                       proof ABSENT → EVAL never ran → the upfront debit is the
 *                                      only money effect → full refund, claim
 *                                      debited→refunded.
 *                       proof UNREADABLE (Redis down) → skip, retry next run.
 *
 * Receiver credits / FairLuck audit of a reconciled intent may still be applied
 * by a late job delivery (processed_jobs-guarded); if the job is permanently
 * lost they need manual replay — flagged MONEY-CRITICAL with full context.
 */
class ReconcileLuckyBatchIntents extends Command
{
    protected $signature = 'lucky:reconcile-intents
        {--minutes=10 : Only touch debited/settled intents older than this many minutes (must exceed the user-lock TTL + gateway timeout so an in-flight stalled request is never compensated under it)}
        {--settling-minutes=20 : Separate, LONGER cutoff for the settling state (S-CON-1): a settling intent is moments from the EVAL, so a wider window makes "refund then EVAL executes after" effectively impossible}
        {--limit=500 : Max intents per run}';

    protected $description = 'Compensate orphaned lucky-gift batch intents (refund or credit) after crashes in the debit/settle/dispatch window.';

    public function handle()
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('minutes')));
        // S-CON-1: the settling state uses its own, longer cutoff (default 2× debited).
        $settlingCutoff = now()->subMinutes(max(1, (int) $this->option('settling-minutes')));
        $limit = max(1, (int) $this->option('limit'));

        $orphans = DB::table('lucky_batch_intents')
            ->where(function ($q) use ($cutoff, $settlingCutoff) {
                $q->where(function ($q2) use ($cutoff) {
                    $q2->whereIn('status', ['debited', 'settled'])->where('created_at', '<', $cutoff);
                })->orWhere(function ($q2) use ($settlingCutoff) {
                    $q2->where('status', 'settling')->where('created_at', '<', $settlingCutoff);
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $handled = 0;
        foreach ($orphans as $intent) {
            try {
                $handled += $intent->status === 'settled'
                    ? $this->reconcileSettled($intent)
                    : $this->reconcileDebited($intent);
            } catch (\Throwable $e) {
                Log::critical('MONEY-CRITICAL: lucky:reconcile-intents failed for intent - will retry next run', [
                    'intent_id' => $intent->id,
                    'nonce' => $intent->nonce,
                    'user_id' => $intent->user_id,
                    'status' => $intent->status,
                    'cost' => $intent->cost,
                    'total_paid' => $intent->total_paid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Serial-path compensation journal: apply claimable pending rows
        // (claim-first tx — exactly once even against a racing inline retry).
        $journalApplied = 0;
        try {
            $journalApplied = \App\Services\Gifts\LuckyMoneyJournal::retryPending();
        } catch (\Throwable $e) {
            Log::error('lucky:reconcile-intents journal retry failed - next run retries', ['error' => $e->getMessage()]);
        }

        // Prune idempotency nonces older than 24h (claims are only needed while
        // a client could still legitimately retry the same request).
        try {
            DB::table('lucky_request_nonces')->where('created_at', '<', now()->subDay())->limit(5000)->delete();
        } catch (\Throwable $e) {
            Log::warning('lucky_request_nonces prune failed', ['error' => $e->getMessage()]);
        }

        $this->info("Reconciled {$handled}/{$orphans->count()} orphaned intents; journal applied {$journalApplied}.");
        return self::SUCCESS;
    }

    /**
     * EVAL committed, post-job credit not confirmed: claim and credit the win.
     */
    private function reconcileSettled(object $intent): int
    {
        $totalPaid = (int) ($intent->total_paid ?? 0);

        $claimed = DB::transaction(function () use ($intent, $totalPaid) {
            $claimed = DB::table('lucky_batch_intents')
                ->where('id', $intent->id)
                ->where('status', 'settled')
                ->update(['status' => 'reconciled', 'updated_at' => now()]);
            if ($claimed === 1 && $totalPaid > 0) {
                DB::table('users')->where('id', $intent->user_id)
                    ->increment('di', $totalPaid, ['updated_at' => now()]);
            }
            return $claimed === 1;
        });

        if ($claimed) {
            Log::critical('MONEY-CRITICAL: reconciled orphaned SETTLED batch intent - win credited; verify post-job receiver credits/audit', [
                'intent_id' => $intent->id,
                'nonce' => $intent->nonce,
                'user_id' => $intent->user_id,
                'cost' => $intent->cost,
                'win_credited' => $totalPaid,
            ]);
        }
        return $claimed ? 1 : 0;
    }

    /**
     * Crashed before the settled-mark: the commit-proof key decides refund vs credit.
     */
    private function reconcileDebited(object $intent): int
    {
        // Throws if Redis is unreachable → caught by handle(), retried next run.
        $proof = FairLuckWallet::vaultRedis()->get(PoolManager::intentProofKey($intent->nonce));

        if ($proof !== null && $proof !== false) {
            $totalPaid = (int) $proof;
            $claimed = DB::transaction(function () use ($intent, $totalPaid) {
                $claimed = DB::table('lucky_batch_intents')
                    ->where('id', $intent->id)
                    ->whereIn('status', ['debited', 'settling'])
                    ->update(['status' => 'reconciled', 'total_paid' => $totalPaid, 'updated_at' => now()]);
                if ($claimed === 1 && $totalPaid > 0) {
                    DB::table('users')->where('id', $intent->user_id)
                        ->increment('di', $totalPaid, ['updated_at' => now()]);
                }
                return $claimed === 1;
            });

            if ($claimed) {
                Log::critical('MONEY-CRITICAL: reconciled orphaned DEBITED batch intent (EVAL committed) - win credited; post-job never dispatched, receiver credits/audit need manual replay', [
                    'intent_id' => $intent->id,
                    'nonce' => $intent->nonce,
                    'user_id' => $intent->user_id,
                    'cost' => $intent->cost,
                    'win_credited' => $totalPaid,
                ]);
            }
            return $claimed ? 1 : 0;
        }

        // Proof ABSENT. If the intent is older than 6 days the proof key may have
        // EXPIRED (7d TTL) rather than never existed — auto-refunding here could
        // re-refund a settled batch. Park it for manual review, balance untouched.
        if ($intent->created_at !== null && now()->subDays(6)->greaterThan($intent->created_at)) {
            $parked = DB::table('lucky_batch_intents')
                ->where('id', $intent->id)
                ->whereIn('status', ['debited', 'settling'])
                ->update(['status' => 'needs_review', 'updated_at' => now()]);
            if ($parked === 1) {
                Log::critical('MONEY-CRITICAL: stale proof-less batch intent parked as needs_review (proof TTL may have expired) - manual review required', [
                    'intent_id' => $intent->id,
                    'nonce' => $intent->nonce,
                    'user_id' => $intent->user_id,
                    'cost' => $intent->cost,
                ]);
            }
            return $parked === 1 ? 1 : 0;
        }

        // EVAL never ran → refund the upfront debit in full.
        $claimed = DB::transaction(function () use ($intent) {
            $claimed = DB::table('lucky_batch_intents')
                ->where('id', $intent->id)
                ->whereIn('status', ['debited', 'settling'])
                ->update(['status' => 'refunded', 'updated_at' => now()]);
            if ($claimed === 1) {
                DB::table('users')->where('id', $intent->user_id)
                    ->increment('di', (int) $intent->cost, ['updated_at' => now()]);
            }
            return $claimed === 1;
        });

        if ($claimed) {
            Log::critical('MONEY-CRITICAL: reconciled orphaned DEBITED batch intent (EVAL never ran) - upfront debit refunded in full', [
                'intent_id' => $intent->id,
                'nonce' => $intent->nonce,
                'user_id' => $intent->user_id,
                'refunded' => (int) $intent->cost,
            ]);
        }
        return $claimed ? 1 : 0;
    }
}
