<?php

namespace App\Jobs;

use App\Classes\Gifts\UpdateUserWhenSendGift;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Public\Http\Services\UpgradeRoomLevelServices;
use Modules\RoomBoom\Services\NewRoomBoomGiftService;
use App\Http\Services\RoomService;
use Carbon\Carbon;
use App\Helpers\CacheHelper;

/**
 * ProcessLuckyGiftPostJob
 *
 * Handles all post-processing operations for lucky gift sends:
 * - Cache updates
 * - User updates
 * - PK dispatch + client-side charisma frame
 * - Room boom processing
 * - Level upgrades
 *
 * This job runs AFTER the main gift transaction completes,
 * allowing the HTTP response to return quickly (< 10 seconds)
 */
class ProcessLuckyGiftPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes max
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Exponential backoff

    public function __construct(
        public array $payload
    ) {
        // Dedup nonce: generated ONCE at dispatch (constructor runs before the job
        // is serialized) and stable across retries. Without it, two REAL combos
        // with identical fields (same gift/count and balance-neutral outcome)
        // hashed to the same identifier and the second was silently dropped —
        // 205 falsely "already processed" jobs measured on VM test.
        $this->payload['job_nonce'] = $this->payload['job_nonce'] ?? (string) \Illuminate\Support\Str::uuid();
    }

    public function handle()
    {
        // ══════════════════════════════════════════════════════════════
        // DEDUPLICATION: Prevent duplicate job execution
        // ══════════════════════════════════════════════════════════════
        $jobIdentifier = $this->getJobIdentifier();

        // Check if this job was already processed
        $alreadyProcessed = DB::table('processed_jobs')
            ->where('job_identifier', $jobIdentifier)
            ->exists();

        if ($alreadyProcessed) {
            Log::info('ProcessLuckyGiftPostJob skipped - already processed', [
                'job_identifier' => $jobIdentifier,
                'user_id' => $this->payload['user_id'] ?? null,
            ]);
            return; // Exit early - job already processed
        }

        try {
            $userId = $this->payload['user_id'] ?? null;
            $roomId = $this->payload['room_id'] ?? null;
            $receiversIds = $this->payload['receivers_ids'] ?? [];
            $giftId = $this->payload['gift_id'] ?? null;
            $coinsForReceiver = $this->payload['coins_for_receiver'] ?? 0;
            $receiverCutMap = $this->payload['receiver_cut_map'] ?? [];
            $totalPrice = $this->payload['total_price'] ?? 0;
            $hostPercentage = $this->payload['host_percentage'] ?? 0;
            $count = $this->payload['count'] ?? 1;
            $totalDiamond = $this->payload['total_diamond'] ?? 0;
            $charizmStatus = $this->payload['charizma_status'] ?? false;
            $lastPk = $this->payload['last_pk'] ?? false;
            $roomType = $this->payload['room_type'] ?? null;
            $ownerId = $this->payload['owner_id'] ?? null;
            $appWalletDiff = $this->payload['app_wallet_diff'] ?? 0;
            $ownerWalletDiff = $this->payload['owner_wallet_diff'] ?? 0;
            $senderLevel = $this->payload['sender_level'] ?? null;
            $roomSession = $this->payload['room_session'] ?? 0;
            // Deferred batch win credit: > 0 ONLY when the synchronous credit
            // failed after batchSettle (vault already settled — the win MUST be
            // paid). Applied inside the dedup-claimed money tx below, exactly once.
            $winCreditPending = (int) ($this->payload['win_credit_pending'] ?? 0);
            $fairLuckPosts = $this->payload['fairluck_posts'] ?? [];
            if (empty($fairLuckPosts) && !empty($this->payload['fairluck_packed'])) {
                $fairLuckPosts = $this->expandPackedFairLuckPosts($this->payload['fairluck_packed']);
            }

            // Orphan-sender guard: stale queued jobs can reference a user that no
            // longer exists (deleted test fixtures hammered the queue with 79K
            // FK-1452 retries on VM test). Sender-FK'd audit writes are dropped;
            // receiver credits below still apply (whereIn touches existing rows only).
            $senderExists = $userId && DB::table('users')->where('id', $userId)->exists();
            if (!$senderExists && (!empty($fairLuckPosts) || $totalDiamond > 0)) {
                Log::warning('ProcessLuckyGiftPostJob: sender no longer exists - skipping sender-FK audit writes', [
                    'user_id' => $userId,
                ]);
                $fairLuckPosts = [];
                $totalDiamond = 0;
            }

            // Profile rows are ensured OUTSIDE the money tx (autocommit, short
            // FK S-lock) so the tx below never has to INSERT into
            // user_luck_profiles while holding other hot locks.
            if (!empty($fairLuckPosts)) {
                $this->ensureLuckProfilesExist($fairLuckPosts);
            }

            // ══════════════════════════════════════════════════════════════
            // MONEY-CRITICAL SECTION — claim + all balance writes in ONE atomic
            // transaction. The dedup marker is claimed FIRST inside the tx, so if
            // anything throws the whole thing rolls back (marker included) and the
            // job can safely retry. If a concurrent worker already claimed it, this
            // tx commits no money writes and returns false (idempotent).
            //
            // FIXED LOCK ORDER (deadlock root fix, verified AB-BA cycle on VM test):
            //   1. processed_jobs (no hot rows)
            //   2. users#sender X-lock taken explicitly FIRST — the old order took
            //      an implicit FK S-lock on this row (fair_luck_transactions INSERT)
            //      and upgraded to X later for total_diamond_send, deadlocking with
            //      any concurrent job of the same sender holding the profile X-lock.
            //   3. user_luck_profiles (ONE aggregated UPDATE)
            //   4. fair_luck_transactions INSERT (FK S-lock already covered by 2)
            //   5. core wallets, then receivers (sorted ids).
            // The 3-attempt transaction retries the rare residual deadlock
            // (e.g. cross sender↔receiver pairs) instead of failing the job.
            // ══════════════════════════════════════════════════════════════
            // Holds the EXACT rows committed for the daily-counter replay below.
            // Overwritten on each tx attempt, so after a successful commit it
            // reflects only the final committed attempt — never a rolled-back one.
            $insertedRows = [];
            $processed = DB::transaction(function () use (
                $jobIdentifier, $userId, $totalDiamond, $senderLevel, $senderExists,
                $appWalletDiff, $ownerWalletDiff, $receiverCutMap, $receiversIds,
                $coinsForReceiver, $fairLuckPosts, $winCreditPending, &$insertedRows
            ) {
                $claimed = DB::table('processed_jobs')->insertOrIgnore([
                    'job_identifier' => $jobIdentifier,
                    'job_type' => self::class,
                    'processed_at' => now(),
                ]);
                if ($claimed === 0) {
                    return false; // another worker owns this job — do no money writes
                }

                // Take the sender row X-lock up-front (fixed lock order — see above).
                if ($senderExists) {
                    DB::table('users')->where('id', $userId)->lockForUpdate()->value('id');
                }

                // SINGLE idempotent win-credit path for the batch combo: the win
                // is credited HERE, inside the dedup-claimed money tx, after also
                // claiming the journal intent (debited/settled → completed). The
                // intent claim makes the credit exactly-once even against
                // lucky:reconcile-intents racing a delayed/retried job: whoever
                // claims the row applies the credit; the other side skips it.
                // Legacy payloads without an intent fall back to the
                // processed_jobs guard alone (unchanged behaviour).
                $intentNonce = $this->payload['job_nonce'] ?? null;
                $hasIntent = !empty($this->payload['batch_intent']) && $intentNonce;
                $intentClaimed = false;
                if ($hasIntent) {
                    $intentClaimed = DB::table('lucky_batch_intents')
                        ->where('nonce', $intentNonce)
                        ->whereIn('status', ['debited', 'settling', 'settled'])
                        ->update(['status' => 'completed', 'updated_at' => now()]) === 1;
                }

                if ($senderExists && $winCreditPending > 0 && (!$hasIntent || $intentClaimed)) {
                    DB::table('users')->where('id', $userId)
                        ->update(['di' => DB::raw("di + {$winCreditPending}"), 'updated_at' => now()]);
                    Log::info('ProcessLuckyGiftPostJob applied batch win credit', [
                        'user_id' => $userId,
                        'amount' => $winCreditPending,
                        'job_nonce' => $intentNonce,
                    ]);
                } elseif ($winCreditPending > 0 && $hasIntent && !$intentClaimed) {
                    Log::warning('ProcessLuckyGiftPostJob win credit skipped - intent already completed/reconciled', [
                        'user_id' => $userId,
                        'amount' => $winCreditPending,
                        'job_nonce' => $intentNonce,
                    ]);
                }

                // Transaction log + profile stats (audit). Exceptions MUST propagate:
                // a swallowed deadlock here used to leave the tx implicitly rolled
                // back while later statements committed autocommit (double-apply +
                // "There is no active transaction" on commit).
                if (!empty($fairLuckPosts)) {
                    $insertedRows = $this->persistFairLuckPosts($fairLuckPosts);
                }

                // Sender diamond/level bookkeeping (NOT the di balance — already saved).
                if ($userId && $totalDiamond > 0) {
                    $updateData = ['total_diamond_send' => DB::raw("total_diamond_send + {$totalDiamond}")];
                    if ($senderLevel !== null) {
                        $updateData['sender_level'] = $senderLevel;
                    }
                    DB::table('users')->where('id', $userId)->update($updateData);
                }

                if ($appWalletDiff !== 0 || $ownerWalletDiff !== 0) {
                    $this->updateCoreWallets($appWalletDiff, $ownerWalletDiff);
                }

                // Receiver credit — EACH receiver gets ONLY their own cut (B1 fix).
                $this->creditReceiversFromMap($receiverCutMap, $receiversIds, (int) $coinsForReceiver);

                return true;
            }, 3);

            if ($processed === false) {
                Log::info('ProcessLuckyGiftPostJob skipped - claimed by another worker', [
                    'job_identifier' => $jobIdentifier,
                ]);
                return;
            }

            // ══════════════════════════════════════════════════════════════
            // BEST-EFFORT SECTION — non-money side effects. These run AFTER the
            // money tx committed and the job is already marked processed, so a
            // failure here must NOT re-run the money writes (each is guarded).
            // ══════════════════════════════════════════════════════════════

            // ── SUPPORTER HISTORY (gift_logs) — FIRST best-effort action ────────
            // Dispatch the supporter-row writer (UpdateUserDataWhenSendGift) BEFORE
            // any other best-effort step, each in its own guard, so a later failure
            // (room boom / level upgrade / Redis blip) can never prevent the
            // supporter row from being written — the historical "money fine but the
            // supporter disappears" bug. The retry path short-circuits on the
            // processed_jobs claim and would NOT re-reach this point, so the row
            // can't lean on job retries; instead exactly-once is enforced at the
            // gift_logs INSERT via the stable job_nonce (passed below), making any
            // re-dispatch a no-op. Failure is logged loudly (no silent swallow) —
            // money already committed, so the job must not fail and spin a sterile
            // retry that drops the row for good.
            if ($userId && $roomId && $giftId) {
                try {
                    $userCoinsAfterCache = $this->payload['user_coins_after'] ?? $this->payload['user_coin_after'] ?? 0;

                    \App\Jobs\UpdateUserDataWhenSendGift::dispatch(
                        (int) $userId,
                        (int) $roomId,
                        array_map('intval', $receiversIds),
                        (int) $giftId,
                        (int) ($this->payload['number'] ?? 0),
                        (int) ($this->payload['price'] ?? 0),
                        (int) $userCoinsAfterCache,
                        (int) ($this->payload['total_count_win'] ?? 0),
                        (int) ($this->payload['total_user_win'] ?? 0),
                        // Stable across retries / re-dispatch (set in constructor) →
                        // the exactly-once key for the gift_logs supporter rows.
                        (string) ($this->payload['job_nonce'] ?? '')
                    )->onQueue('luckyGift');
                } catch (\Throwable $e) {
                    Log::error('lucky gift_logs writer dispatch failed (supporter row may be delayed)', [
                        'user_id' => $userId,
                        'room_id' => $roomId,
                        'gift_id' => $giftId,
                        'job_nonce' => $this->payload['job_nonce'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // O(1) daily report counters — best-effort, AFTER the money tx committed.
            // Replays the EXACT rows just inserted; never runs on rollback or on the
            // duplicate-claim path (the closure returns false → we returned above),
            // so the day hash equals SUM(...) over fair_luck_transactions. Wrapped
            // so a Redis blip never fails the job — the report self-heals via the
            // on-read seed and the 10-min reconciler.
            if (!empty($insertedRows)) {
                try {
                    $this->bumpDailyLuckyCounters($insertedRows);
                } catch (\Throwable $e) {
                    Log::warning('lucky daily counters bump failed', ['error' => $e->getMessage()]);
                }
            }

            if ($roomId && $roomSession > 0) {
                try {
                    Room::where('id', $roomId)->increment('session', $roomSession);
                } catch (\Throwable $e) {
                    Log::warning('Failed to update room session', ['room_id' => $roomId, 'error' => $e->getMessage()]);
                }
            }

            // ── UNIFIED RANKING: lucky section + wealth (RankingScoreService) ───
            // lucky: FULLY ISOLATED, measures SPENDING (totalDiamond / turnover).
            //
            // wealth(sender): the sender's lucky contribution to WEALTH is the same
            // TURNOVER ($totalDiamond), NOT the lucky gift_logs giftPrice (which is
            // the HOST CUT and stays out of wealth — the backfill excludes lucky
            // rows from the gift_logs wealth sum). Adding it here with the SAME value
            // the backfill merges from fair_luck_transactions.bet_amount keeps live
            // and backfill identical, so the 15-min RENAME does not oscillate wealth.
            // charm/charisma/lucky-section are unaffected.
            //
            // Post-commit + best-effort: a Redis blip must not fail the job (money
            // already committed); ranking:backfill self-heals from fair_luck turnover.
            if ($userId && $totalDiamond > 0) {
                try {
                    $rankingScores = app(\App\Services\RankingScoreService::class);
                    $rankingScores->add('lucky', (int) $userId, (int) $totalDiamond);
                    $rankingScores->add('wealth', (int) $userId, (int) $totalDiamond);
                } catch (\Throwable $e) {
                    Log::warning('lucky/wealth ranking redis add failed (best-effort)', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                // PK attribution PER AMOUNT GROUP: each receiver's meter is
                // incremented by THEIR OWN cut, never the aggregate of the whole send.
                // PK keeps the legacy integer map + >1 gate and rides dispatchRoomsRedis.
                if ($lastPk && $roomId && $userId) {
                    $groups = self::groupReceiverCuts($receiverCutMap, $receiversIds, (int) $coinsForReceiver);
                    foreach ($groups as $amount => $ids) {
                        if ($amount <= 1) {
                            continue;
                        }
                        dispatchRoomsRedis($roomId, $userId, $amount, $ids, "pk");
                    }
                }
            } catch (\Throwable $e) {
                // Best-effort broadcast — a Redis blip here must not fail the job
                // (money already committed; a retry would just skip on the marker).
                Log::warning('Failed to dispatch rooms redis broadcast', ['room_id' => $roomId, 'error' => $e->getMessage()]);
            }

            // ── SERVER-AUTHORITATIVE CHARISMA: the backend owns the per-room,
            // per-receiver cumulative total in Redis (RoomCharismaStore) and ships
            // the resulting TOTAL inside the in-room gift frame; the client only
            // renders. The increment is the SAME value already credited to each
            // receiver's wallet — the EVAL-realized integer cut (receiver_cut_map),
            // which is also the lucky gift_logs giftPrice basis. This removes the old
            // share = unitPrice × receiverFeeRate basis entirely, killing the
            // 0-share frame-drop (a 0% / sub-coin receiver rate no longer suppresses
            // the badge). The frame is ALWAYS sent when charisma is on and at least
            // one receiver was credited — the cumulative total is meaningful even if
            // a single send rounds small. Best-effort: a publish blip must not fail
            // the job (money already committed).
            if ($charizmStatus && $roomId && !empty($receiverCutMap)) {
                try {
                    $byReceiver = [];
                    foreach ($receiverCutMap as $rid => $cut) {
                        $cut = (int) $cut;
                        if ($cut > 0) {
                            $byReceiver[(int) $rid] = $cut;
                        }
                    }
                    if (!empty($byReceiver)) {
                        $newTotals = \App\Services\RoomCharismaStore::increment((int) $roomId, $byReceiver);
                        $totalsOut = [];
                        foreach ($newTotals as $rid => $total) {
                            $totalsOut[(string) $rid] = $total;
                        }
                        if (!empty($totalsOut)) {
                            \App\Helpers\Common::sendToStream(
                                'SendCustomCommand',
                                (int) $roomId,
                                (int) $userId,
                                json_encode([
                                    'messageContent' => [
                                        'message' => 'showGifts',
                                        'send_id' => (int) $userId,
                                        'receiver_charisma_totals' => $totalsOut,
                                    ],
                                ], JSON_UNESCAPED_UNICODE)
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('lucky server-authoritative charisma frame publish failed (best-effort)', [
                        'room_id' => $roomId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($roomId && $totalPrice > 0) {
                $this->processRoomBoom($roomId, $totalPrice, $hostPercentage, $count, $userId);
            }

            if ($roomType === 'audio' && $roomId && $totalPrice > 0) {
                $this->upgradeRoomLevel($roomId, $totalPrice, $count);
            }
        } catch (\Throwable $e) {
            // The money tx rolled back (marker released) on failure — allow retry.
            Log::error('ProcessLuckyGiftPostJob failed', [
                'error' => $e->getMessage(),
                'payload' => $this->payload,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Credit each receiver ONLY their own accumulated cut.
     *
     * Prefers the exact per-receiver map (built by the engine split). Falls back to
     * the legacy single-amount behaviour only when no map is present (older jobs in
     * flight). Throws on failure so the surrounding money transaction rolls back.
     */
    private function creditReceiversFromMap(array $receiverCutMap, array $receiversIds, int $coinsForReceiver): void
    {
        $updater = app(UpdateUserWhenSendGift::class);

        // GLOBAL ascending lock order over ALL receiver rows BEFORE the grouped
        // UPDATEs: the per-amount groups are issued as separate statements, and
        // two concurrent jobs with different amount groupings could otherwise
        // lock the same receivers in opposite orders (AB-BA deadlock). Locking
        // every receiver id ascending up-front makes group order irrelevant.
        $allIds = array_map('intval', !empty($receiverCutMap) ? array_keys($receiverCutMap) : $receiversIds);
        $allIds = array_values(array_unique(array_filter($allIds)));
        sort($allIds);
        if (!empty($allIds)) {
            DB::table('users')->whereIn('id', $allIds)->orderBy('id')->lockForUpdate()->pluck('id');
        }

        if (!empty($receiverCutMap)) {
            // Group receivers by identical amount to minimise queries.
            $byAmount = [];
            foreach ($receiverCutMap as $rid => $amount) {
                $amount = (int) $amount;
                if ($amount > 0) {
                    $byAmount[$amount][] = (int) $rid;
                }
            }
            ksort($byAmount); // deterministic statement order on top of the pre-lock
            foreach ($byAmount as $amount => $ids) {
                $updater->updateUsers($amount, $ids);
            }
            return;
        }

        // Legacy fallback (in-flight jobs without a map): EQUAL SPLIT of the
        // aggregate — never the full aggregate to every receiver (that minted
        // (N−1)× the receiver cut on multi-receiver sends).
        if (!empty($receiversIds) && $coinsForReceiver > 0) {
            $each = intdiv($coinsForReceiver, max(1, count($receiversIds)));
            if ($each > 0) {
                $updater->updateUsers($each, array_map('intval', $receiversIds));
            }
        }
    }

    /**
     * Group receivers by their own accumulated cut. Pure (unit-tested).
     * Falls back to an EQUAL split of the aggregate when no map exists.
     *
     * @return array<int, int[]> [amount => receiverIds]
     */
    public static function groupReceiverCuts(array $receiverCutMap, array $receiversIds, int $aggregate): array
    {
        if (!empty($receiverCutMap)) {
            $byAmount = [];
            foreach ($receiverCutMap as $rid => $amount) {
                $amount = (int) $amount;
                if ($amount > 0) {
                    $byAmount[$amount][] = (int) $rid;
                }
            }
            ksort($byAmount);
            return $byAmount;
        }

        $n = count($receiversIds);
        if ($n === 0 || $aggregate <= 0) {
            return [];
        }
        $each = intdiv($aggregate, $n);

        return $each > 0 ? [$each => array_map('intval', $receiversIds)] : [];
    }

    /**
     * Ensure user_luck_profiles rows exist for every stats user, OUTSIDE the
     * money transaction (autocommit), so the tx never INSERTs into the profile
     * table (whose users FK takes an S-lock) while holding other hot locks.
     */
    private function ensureLuckProfilesExist(array $posts): void
    {
        $manager = app(\App\Services\FairLuck\ProfileManager::class);
        $seen = [];
        foreach ($posts as $post) {
            $uid = (int) ($post['stats']['user_id'] ?? 0);
            if ($uid > 0 && !isset($seen[$uid])) {
                $seen[$uid] = true;
                $manager->getProfile($uid);
            }
        }
    }

    /**
     * Persist the deferred FairLuck DB writes emitted by the Redis-authoritative
     * processBet path: apply ONE aggregated profile-stat increment per user, then
     * batch-insert the transaction log. Vault balance durability is handled
     * separately by the fairluck:sync-wallets snapshot — NOT here.
     *
     * Runs INSIDE the money transaction. Exceptions intentionally propagate so
     * the whole tx (dedup marker included) rolls back and the retry re-applies
     * everything exactly once — the old swallowing catches here turned every
     * deadlock into autocommit double-apply + "There is no active transaction".
     *
     * Returns the EXACT transaction rows inserted (with created_at applied) so the
     * caller can replay them into the O(1) daily report counters AFTER the money tx
     * commits — without rebuilding them. Empty array when nothing was inserted.
     *
     * @param array<int, array{stats: array, transaction: array}> $posts
     * @return array<int, array> the inserted fair_luck_transactions rows
     */
    private function persistFairLuckPosts(array $posts): array
    {
        $now = now();
        $rows = [];
        $aggByUser = [];

        foreach ($posts as $post) {
            $tx = $post['transaction'] ?? null;
            if ($tx) {
                $tx['is_winner'] = !empty($tx['is_winner']) ? 1 : 0;
                $tx['is_beginner_protected'] = !empty($tx['is_beginner_protected']) ? 1 : 0;
                $tx['wallets_before'] = isset($tx['wallets_before']) ? json_encode($tx['wallets_before']) : null;
                $tx['wallets_after'] = isset($tx['wallets_after']) ? json_encode($tx['wallets_after']) : null;
                $tx['created_at'] = $now;
                $rows[] = $tx;
            }

            $stats = $post['stats'] ?? null;
            if (!$stats || empty($stats['user_id'])) {
                continue;
            }
            $uid = (int) $stats['user_id'];
            if (!isset($aggByUser[$uid])) {
                $aggByUser[$uid] = ['total_bets' => 0.0, 'total_profit' => 0.0, 'bet_count' => 0, 'win_count' => 0, 'new_deviation' => 0.0];
            }
            $aggByUser[$uid]['total_bets'] += (float) ($stats['bet_amount'] ?? 0);
            $aggByUser[$uid]['total_profit'] += (float) ($stats['profit_amount'] ?? 0);
            $aggByUser[$uid]['bet_count'] += 1;
            $aggByUser[$uid]['win_count'] += !empty($stats['is_winner']) ? 1 : 0;
            // Last bet's deviation wins — same end state the old per-bet loop left.
            $aggByUser[$uid]['new_deviation'] = (float) ($stats['new_deviation'] ?? 0);
        }

        // 1) ONE aggregated increment per user (was one UPDATE per bet — up to 999
        //    X-lock re-acquisitions on the same hot profile row per tx). Sorted ids
        //    keep the lock order deterministic across concurrent jobs.
        $manager = app(\App\Services\FairLuck\ProfileManager::class);
        ksort($aggByUser);
        foreach ($aggByUser as $uid => $agg) {
            $manager->applyAggregatedStats(
                $uid,
                $agg['total_bets'],
                $agg['total_profit'],
                $agg['bet_count'],
                $agg['win_count'],
                $agg['new_deviation']
            );
        }

        // 2) Batch-insert FairLuckTransaction rows (single INSERT; the users FK
        //    S-lock is already covered by the up-front sender X-lock).
        if (!empty($rows)) {
            DB::table('fair_luck_transactions')->insert($rows);
        }

        return $rows;
    }

    /**
     * O(1) daily report counters for the admin lucky-gift-reports "today" cards.
     *
     * Replays the EXACT rows just committed to fair_luck_transactions into a per-day
     * Redis hash (one HINCRBY field per SUM the report runs) plus a per-day SET of
     * distinct senders (SCARD = COUNT(DISTINCT user_id), exact). Issued as ONE
     * pipelined round-trip per day bucket on the SAME durable vault Redis the engine
     * already uses, AFTER the money tx committed and OUTSIDE any lock — so it adds
     * no lock time to the money path. Every field is a sum of a per-row term, so the
     * hash is provably equal, field-by-field, to the report's GROUP-less aggregate.
     *
     * @param array<int, array> $rows the committed fair_luck_transactions rows
     */
    private function bumpDailyLuckyCounters(array $rows): void
    {
        // All rows in one job share created_at = now() (set in persistFairLuckPosts),
        // so they belong to one day bucket. Group defensively in case a future caller
        // mixes days within one batch.
        $byDay = [];
        foreach ($rows as $r) {
            $date = Carbon::parse($r['created_at'])->toDateString();
            if (!isset($byDay[$date])) {
                $byDay[$date] = [
                    'rounds' => 0, 'total_bets' => 0, 'senders_net' => 0,
                    'total_payouts' => 0, 'receivers_total_bps' => 0, 'app_total_bps' => 0,
                    'wins' => 0, 'uids' => [],
                ];
            }
            $win = (int) ($r['is_winner'] ?? 0) === 1;
            $byDay[$date]['rounds']          += 1;
            $byDay[$date]['total_bets']      += (int) ($r['bet_amount'] ?? 0);
            $byDay[$date]['senders_net']     += (int) ($r['profit_amount'] ?? 0);
            $byDay[$date]['total_payouts']   += $win ? ((int) ($r['profit_amount'] ?? 0) + (int) ($r['bet_amount'] ?? 0)) : 0;
            // FRACTION-SAFE receiver fee: receiver_fee is decimal(12,2) (0.50 on a
            // 50-coin bet at 1%). (int) here floored every sub-coin fee to 0 — the
            // report receiver total read 0 for cheap gifts. Accumulate in bps-coins
            // (× 10000, exact integer) like app_total_bps; the reader divides by
            // 10000 to recover the exact decimal == SQL SUM(receiver_fee).
            $byDay[$date]['receivers_total_bps'] += (int) round(((float) ($r['receiver_fee'] ?? 0)) * 10000);
            // FRACTION-SAFE owner fee: app_fee is the EXACT decimal owner cut (e.g.
            // 0.10 on a 10-coin bet at 1%). HINCRBY is integer-only, so casting
            // app_fee to int here floored every sub-coin fee to 0 — the report owner
            // total read 0 for all cheap gifts. Accumulate in bps-coins instead:
            // round(app_fee * 10000) is an exact integer, summed losslessly, and the
            // reader divides by 10000 to recover the exact decimal. SUM stays equal,
            // coin-for-coin, to SQL SUM(app_fee) = ownerRate × turnover.
            $byDay[$date]['app_total_bps']   += (int) round(((float) ($r['app_fee'] ?? 0)) * 10000);
            $byDay[$date]['wins']            += $win ? 1 : 0;
            $byDay[$date]['uids'][(int) ($r['user_id'] ?? 0)] = true;
        }

        $redis = \App\Models\FairLuckWallet::vaultRedis();
        foreach ($byDay as $date => $b) {
            $hkey = "lucky:day:{$date}";
            $pkey = "lucky:day:{$date}:players";
            $redis->pipeline(function ($pipe) use ($hkey, $pkey, $b) {
                $pipe->hincrby($hkey, 'rounds', $b['rounds']);
                $pipe->hincrby($hkey, 'total_bets', $b['total_bets']);
                $pipe->hincrby($hkey, 'senders_net', $b['senders_net']);
                $pipe->hincrby($hkey, 'total_payouts', $b['total_payouts']);
                // bps-coins (= receiver fee × 10000); the report reads /10000 → exact decimal.
                $pipe->hincrby($hkey, 'receivers_total_bps', $b['receivers_total_bps']);
                // bps-coins (= owner fee × 10000); the report reads /10000 → exact decimal.
                $pipe->hincrby($hkey, 'app_total_bps', $b['app_total_bps']);
                $pipe->hincrby($hkey, 'wins', $b['wins']);
                foreach (array_keys($b['uids']) as $uid) {
                    if ($uid > 0) {
                        $pipe->sadd($pkey, $uid);
                    }
                }
                $pipe->expire($hkey, 172800);
                $pipe->expire($pkey, 172800);
            });
        }
    }

    /**
     * Expand the compact batch wire format back into the EXACT per-bet rows
     * persistFairLuckPosts() has always written.
     *
     * The batch sender ships the per-batch constants once ('common') plus the only
     * two values that vary per bet: [appliedPayout, multiplier]. Every other field
     * is a deterministic recurrence (sender balance and lucky-wallet evolve by
     * bet_amount / net_to_vault / appliedPayout) — replayed here identically to the
     * loop that used to build the full rows inline, so the persisted row shape and
     * values are byte-for-byte the same as the legacy 'fairluck_posts' payload.
     *
     * @return array<int, array{stats: array, transaction: array}>
     */
    private function expandPackedFairLuckPosts(array $packed): array
    {
        $c    = $packed['common'] ?? [];
        $bets = $packed['bets'] ?? [];

        $userId        = (int) ($c['user_id'] ?? 0);
        $unitPrice     = (int) ($c['bet_amount'] ?? 0);
        $netToVault    = (int) ($c['net_to_vault'] ?? 0);
        $senderBalance = (int) ($c['sender_balance_start'] ?? 0);
        $runningWallet = (int) ($c['vault_start'] ?? 0);

        // RTP deviation recurrence — identical to FairLuckServiceV7::processBet:
        //   deviation_before = targetRTP - (received/spent  before the bet)
        //   new_deviation    = targetRTP - (received/spent  after  the bet)
        // evolved with the ACTUAL applied payouts. Older in-flight payloads
        // without the start counters keep the legacy 0s (no fabricated values).
        $hasRtp    = array_key_exists('total_spent_start', $c);
        $targetRTP = (float) ($c['target_rtp'] ?? 0.0);
        $spent     = (int) ($c['total_spent_start'] ?? 0);
        $received  = (int) ($c['total_received_start'] ?? 0);

        // Legacy per-unit fallbacks (constant across the combo) for any in-flight
        // payload enqueued before the per-bet fix — new payloads carry exact cuts at
        // bet positions [2]=app_fee [3]=receiver_fee [4]=net_to_vault. app_fee is the
        // EXACT decimal owner fee (e.g. 0.50) stored in the decimal(12,2) column, so
        // SUM(app_fee) equals exactly ownerRate × turnover — the auditable owner-fee
        // truth, decoupled from the integer whole-coin realization timing in Redis.
        $commonAppFee      = (float) ($c['app_fee'] ?? 0);
        // EXACT decimal (decimal(12,2) column) — int here re-floored sub-coin fees.
        $commonReceiverFee = (float) ($c['receiver_fee'] ?? 0);

        $posts = [];
        foreach ($bets as $bet) {
            $applied    = (int) ($bet[0] ?? 0);
            $multiplier = (int) ($bet[1] ?? 0);
            $betAppFee      = isset($bet[2]) ? (float) $bet[2] : $commonAppFee;
            $betReceiverFee = isset($bet[3]) ? (float) $bet[3] : $commonReceiverFee;
            $betNetToVault  = isset($bet[4]) ? (int) $bet[4] : $netToVault;
            $isWinner   = $applied > 0;
            $netProfit  = $isWinner ? ($applied - $unitPrice) : -$unitPrice;

            $walletBefore  = $runningWallet + $betNetToVault;
            $walletAfter   = $walletBefore - $applied;
            $runningWallet = $walletAfter;
            $senderAfter   = $senderBalance - $unitPrice + $applied;

            $deviationBefore = $hasRtp ? $targetRTP - ($spent > 0 ? $received / $spent : 0) : 0;
            $spent    += $unitPrice;
            $received += $applied;
            $newDeviation = $hasRtp ? $targetRTP - ($spent > 0 ? $received / $spent : 0) : 0;

            $posts[] = [
                'stats' => [
                    'user_id'       => $userId,
                    'bet_amount'    => $unitPrice,
                    'profit_amount' => $netProfit,
                    'is_winner'     => $isWinner,
                    'new_deviation' => $newDeviation,
                ],
                'transaction' => [
                    'user_id'                => $userId,
                    'gift_id'                => $c['gift_id'] ?? null,
                    'bet_amount'             => $unitPrice,
                    'app_fee'                => $betAppFee,
                    'receiver_fee'           => $betReceiverFee,
                    'is_winner'              => $isWinner,
                    'multiplier'             => $isWinner ? $multiplier : null,
                    'profit_amount'          => $netProfit,
                    'deviation_before'       => $deviationBefore,
                    'calculated_probability' => 0,
                    'is_beginner_protected'  => false,
                    'protection_multiplier'  => 1.0,
                    'room_id'                => $c['room_id'] ?? null,
                    'sender_balance_before'  => $senderBalance,
                    'sender_balance_after'   => $senderAfter,
                    'wallets_before'         => ['lucky_wallet' => $walletBefore],
                    'wallets_after'          => ['lucky_wallet' => $walletAfter],
                ],
            ];

            $senderBalance = $senderAfter;
        }

        return $posts;
    }

    /**
     * Update core wallets. Self-contained (the old LuckyGiftService that owned this
     * SQL is removed) — only legacy in-flight payloads carry non-zero diffs; the
     * LuckyEngine path never sets app_wallet_diff/owner_wallet_diff. Runs inside the
     * money tx — exceptions must propagate (no swallowing) so the tx rolls back whole.
     */
    private function updateCoreWallets(float $appWalletDiff, float $ownerWalletDiff): void
    {
        DB::update(
            'UPDATE core_wallets
             SET coins = CASE
                 WHEN name = "owner_wallet" THEN coins + :owner_wallet
                 WHEN name = "app_wallet" THEN coins + :app_wallet
             END
             WHERE name IN ("owner_wallet", "app_wallet")',
            ['owner_wallet' => $ownerWalletDiff, 'app_wallet' => $appWalletDiff]
        );
    }

    /**
     * Process room boom gift
     */
    private function processRoomBoom(
        int $roomId,
        float $totalPrice,
        float $hostPercentage,
        int $count,
        int $userId
    ): void {
        try {
            $settings = CacheHelper::cacheSettings();
            if (gettype($settings) !== 'array') {
                $settings = $settings->pluck('value', 'key')->toArray();
            }

            $roomBoomSettings = $settings['room_boom'] ?? 1;
            $totalHostDiamond = (int)($totalPrice * $hostPercentage);

            if ($roomBoomSettings) {
                $room = Room::find($roomId);
                if ($room) {
                    (new NewRoomBoomGiftService())->sendGift($room, $totalHostDiamond, $userId);
                }
            } else {
                $tz = getTimezone();
                $todayStart = Carbon::now($tz)->startOfDay()->copy()->setTimezone('UTC');
                $totalRoomGift = (new RoomService())->getOrCreateTotalRoomGift($roomId, $todayStart);
                $totalRoomGift->increment('current_total', $totalHostDiamond);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to process room boom in ProcessLuckyGiftPostJob', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
            ]);
        }
    }

    /**
     * Upgrade room level
     */
    private function upgradeRoomLevel(int $roomId, float $totalPrice, int $count): void
    {
        try {
            $room = Room::find($roomId);
            if ($room) {
                $serviceLevel = new UpgradeRoomLevelServices();
                // total_price is now the FULL standing deduction of the combo —
                // multiplying by count again would over-credit room XP ×count².
                $serviceLevel->sendGift($room, $totalPrice);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to upgrade room level in ProcessLuckyGiftPostJob', [
                'error' => $e->getMessage(),
                'room_id' => $roomId,
            ]);
        }
    }

    /**
     * Generate unique identifier for this job
     *
     * Creates a unique hash based on critical payload fields to identify
     * duplicate job executions. Uses user_id, receivers, and coins to ensure
     * the same operation isn't processed twice.
     *
     * @return string MD5 hash of critical payload fields
     */
    private function getJobIdentifier(): string
    {
        // Create identifier from critical fields that make this job unique
        $criticalData = [
            'user_id' => $this->payload['user_id'] ?? null,
            'receivers_ids' => $this->payload['receivers_ids'] ?? [],
            'coins_for_receiver' => $this->payload['coins_for_receiver'] ?? 0,
            'room_id' => $this->payload['room_id'] ?? null,
            'gift_id' => $this->payload['gift_id'] ?? null,
            'count' => $this->payload['count'] ?? 1,
            'user_coins_before' => $this->payload['user_coins_before'] ?? 0,
            'user_coins_after' => $this->payload['user_coins_after'] ?? 0,
            // Per-dispatch nonce (set in the constructor, stable across retries).
            // The fields above are NOT unique across real requests — two combos
            // with identical params and balance-neutral outcomes collided and the
            // second one's receiver credits/audit rows were dropped for good.
            'job_nonce' => $this->payload['job_nonce'] ?? null,
        ];

        return md5(json_encode($criticalData));
    }

    public function failed(\Throwable $exception)
    {
        Log::error('ProcessLuckyGiftPostJob permanently failed', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}

