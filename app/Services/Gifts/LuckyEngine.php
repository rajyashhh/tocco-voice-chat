<?php

namespace App\Services\Gifts;

use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\Charge;
use App\Models\FairLuckSetting;
use App\Models\FairLuckWallet;
use App\Traits\Gifts\WinLuckyGift;
use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use App\Services\FairLuck\V7\MultiplierTable;
use App\Services\FairLuck\V7\PoolManager;
use App\Services\FairLuck\V7\EconomySplitter;
use App\Services\FairLuck\V7\BeginnerProtection;

/**
 * LuckyEngine — the SINGLE unified lucky-gift money path (replaces the serial /
 * batch / fallback trio of LuckyGiftService). One atomic upfront clamp+debit, one
 * pure-PHP draw, one batchSettle EVAL, one post-commit dispatch.
 *
 * Frontend contract (POST /v2/send-lucky-gift-combo) is byte-stable: same combo[]
 * / balances / summary / position[] / user_coins (response built from
 * getResponseData2() shape, G3).
 *
 * 12-step flow (§5.1):
 *  [A] idempotency  : nonce claim (UNIQUE(user_id,nonce), B-CON-2) + S-ECO-3
 *                     rebuild-from-proof + 45s user lock.
 *  [B] validation   : gift/room/receivers + 1000-hit cap + threshold read (pre-money).
 *  [C] money win-1  : ONE atomic tx — SELECT di FOR UPDATE, clamp N'=min(N,
 *                     floor(di/unitPrice)), debit cost(N'), insert intent (S-CON-4).
 *  [D] draw         : pure PHP — split + redistribution + per-hit result PAIR
 *                     (boosted/normal) + advisory lock-step.
 *  [E] money win-2  : claim debited→settling, batchSettle EVAL (solvency + bp +
 *                     streak + commit-proof), mark settled.
 *  [F] post-commit  : ProcessLuckyGiftPostJob (idempotent: receiver credits, win
 *                     credit, audit, ranking, banner/sound).
 */
class LuckyEngine
{
    use WinLuckyGift;

    private UpdateUserWhenSendGift $updateUserWhenSendGift;

    /**
     * Public entry — wraps the flow with idempotency + the user lock. Mirrors the
     * proven LuckyGiftService::sendLuckyGiftV2 wrapper verbatim.
     */
    public function send(array $data, User $user, UpdateUserWhenSendGift $updateUserWhenSendGift): array
    {
        $nonce = isset($data['nonce']) && is_string($data['nonce']) && $data['nonce'] !== ''
            ? $data['nonce'] : null;

        if ($nonce !== null) {
            $claimed = DB::table('lucky_request_nonces')->insertOrIgnore([
                'user_id'    => $user->id,
                'nonce'      => $nonce,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($claimed === 0) {
                $existing = DB::table('lucky_request_nonces')
                    ->where('user_id', $user->id)->where('nonce', $nonce)
                    ->first(['response']);
                if ($existing && $existing->response !== null) {
                    return json_decode($existing->response, true); // exact replay
                }
                // S-ECO-3: claimed but no response yet — if the intent already settled
                // and the commit-proof exists, rebuild a concise reconciled response
                // now (per G1: concise, not byte-identical) instead of stalling the
                // user until lucky:reconcile-intents runs.
                $rebuilt = $this->rebuildFromProof($user->id, $nonce);
                if ($rebuilt !== null) {
                    return $rebuilt;
                }
                throw new InvalidArgumentException(__('api_responses.gift_in_progress'));
            }
        }

        try {
            $userLock = $this->acquireUserLock($user->id);
        } catch (\Throwable $e) {
            $this->releaseNonceClaim($user->id, $nonce);
            throw $e;
        }
        try {
            $response = $this->doSend($data, $user, $updateUserWhenSendGift);
            if ($nonce !== null) {
                try {
                    DB::table('lucky_request_nonces')
                        ->where('user_id', $user->id)->where('nonce', $nonce)
                        ->update(['response' => json_encode($response), 'updated_at' => now()]);
                } catch (\Throwable $e) {
                    Log::warning('lucky nonce response store failed (claim still blocks re-charge)', [
                        'user_id' => $user->id, 'nonce' => $nonce, 'error' => $e->getMessage(),
                    ]);
                }
            }
            return $response;
        } catch (InvalidArgumentException | ValidationException $e) {
            // Validation-class failures are thrown strictly BEFORE any money moves.
            $this->releaseNonceClaim($user->id, $nonce);
            throw $e;
        } finally {
            optional($userLock)->release();
        }
    }

    // Lock TTL must outlive the WORST-CASE combo (clamped to ≤1000 hits) — 45s.
    private function acquireUserLock(int $userId, int $timeoutSeconds = 45): \Illuminate\Contracts\Cache\Lock
    {
        $lock = Cache::lock("lucky_gift_lock:user:{$userId}", $timeoutSeconds);
        if (!$lock->get()) {
            Log::channel('lucky_gift')->warning('Lock timeout - gift already in progress', [
                'user_id' => $userId,
            ]);
            throw new InvalidArgumentException(__('api_responses.gift_in_progress'));
        }
        return $lock;
    }

    private function releaseNonceClaim(int $userId, ?string $nonce): void
    {
        if ($nonce === null) {
            return;
        }
        try {
            DB::table('lucky_request_nonces')
                ->where('user_id', $userId)->where('nonce', $nonce)
                ->whereNull('response')->delete();
        } catch (\Throwable $e) {
            // Worst case: the claim stays until the 24h prune (lockout, not money).
        }
    }

    /**
     * S-ECO-3 / G1: a nonce was claimed but its response is missing AND the original
     * request is no longer running. If the batch intent for this user settled and the
     * commit-proof key exists, return a CONCISE reconciled response (correct totals,
     * no per-bet detail) rather than the byte-identical one. Returns null if nothing
     * is recoverable yet (→ caller surfaces gift_in_progress).
     */
    private function rebuildFromProof(int $userId, string $nonce): ?array
    {
        try {
            $intent = DB::table('lucky_batch_intents')
                ->where('request_nonce', $nonce)
                ->where('user_id', $userId)
                ->whereIn('status', ['settled', 'completed', 'reconciled'])
                ->orderByDesc('id')
                ->first(['total_paid']);
            if ($intent === null) {
                return null;
            }
            $totalPaid = (int) ($intent->total_paid ?? 0);
            $coins = (int) (User::where('id', $userId)->value('di') ?? 0);

            return [
                'reconciled'  => true,
                'combo'       => [],
                'summary'     => [
                    'total_win'       => $totalPaid,
                    'max_single_win'  => 0,
                    'total_win_count' => 0,
                ],
                'win_total'   => $totalPaid,
                'user_coins'  => $coins,
                'balances'    => ['sender' => ['after' => $coins]],
            ];
        } catch (\Throwable $e) {
            Log::warning('lucky rebuildFromProof failed', ['user_id' => $userId, 'nonce' => $nonce, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Steps [B]–[F]. Validation + atomic clamp/debit + draw + settle + dispatch.
     */
    private function doSend(array $data, User $user, UpdateUserWhenSendGift $updateUserWhenSendGift): array
    {
        $this->updateUserWhenSendGift = $updateUserWhenSendGift;
        $userId  = (int) $user->id;
        $ownerId = $data['owner_id'] ?? null;
        $roomId  = $data['room_id'] ?? null;
        $giftId  = $data['id'];
        $number  = (int) $data['num'];
        $count   = (int) ($data['count'] ?? 1);
        $requestNonce = isset($data['nonce']) && is_string($data['nonce']) && $data['nonce'] !== '' ? $data['nonce'] : null;

        // ── [B] VALIDATION (before any money) ──────────────────────────────────
        $receiverFeeRate    = FairLuckSetting::getReceiverFeeRate();
        $receiverPayoutRate = $receiverFeeRate;

        $gift = Gift::query()
            ->select(['id', 'name', 'e_name', 'type', 'price', 'vip_level', 'is_play', 'img', 'show_img', 'show_img2'])
            ->where('id', $giftId)->where('enable', 1)->first();
        if (!$gift) {
            throw new InvalidArgumentException(__('api_responses.giftNotFound'));
        }
        $giftPrice = (int) $gift->price;

        if ($ownerId !== null) {
            $room = Room::withoutAppends()->with('microphones')->where('uid', $ownerId)
                ->selectRaw('id,uid,play_num,room_pass,session,total_diamond,level,type,level_id,microphone,charizma_status')->first();
        } else {
            $room = Room::withoutAppends()->with('microphones')->where('id', $roomId)
                ->selectRaw('id,uid,play_num,room_pass,session,total_diamond,level,type,level_id,microphone,charizma_status')->first();
            $ownerId = $room?->uid;
        }
        if (!$room) {
            throw new InvalidArgumentException(__('api_responses.roomNotFound'));
        }
        $roomId = (int) $room->id;

        $receiversIds = array_map('intval', array_map('trim', explode(',', $data['toUid'])));
        $receivedUsers = User::whereIn('id', $receiversIds)->select(['id', 'name', 'agency_id'])->get();
        $receiverName  = $receivedUsers->first()?->name;
        $receiversIds  = $receivedUsers->pluck('id')->all();
        $receiversCount = count($receiversIds);
        if ($receiversCount === 0) {
            throw new InvalidArgumentException(__('api_responses.roomNotFound'));
        }

        // Hard per-request hit budget: count × receivers ≤ 1000 (lock-TTL safety).
        $totalHits = $count * max(1, $receiversCount);
        if ($totalHits > 1000) {
            throw new InvalidArgumentException(__('api_responses.validation_error'));
        }

        $isToRoom = $receiversCount > 1;
        $unitPrice = $giftPrice * $number; // gross bet per hit
        if ($unitPrice <= 0) {
            throw new InvalidArgumentException(__('api_responses.validation_error'));
        }

        // Banner/popular threshold — resolved ONCE, BEFORE any debit.
        $luckyGiftCoinsThreshold = (int) (\App\Helpers\Common::getSettingsValue('lucky_gift_coins') ?: 2000);

        $responseData = $this->getResponseData2($gift, $room, $user, $receiversIds, $this->getReceiverName($isToRoom, $receiverName));

        // Deterministic schedule: `count` rounds × receivers in order.
        $requestedHits = $count * $receiversCount;
        $batchJobNonce = (string) Str::uuid();
        $senderBalanceBefore = (int) $user->di;
        $oldUserCoin = (int) $user->di;

        // ── [C] MONEY WINDOW 1 — atomic clamp + debit + intent (S-CON-4) ────────
        // SELECT FOR UPDATE the sender's row, clamp the affordable hit count, debit
        // cost(N') and write the intent — ALL in one tx so a concurrent ordinary
        // gift can never race the clamp. N'=0 → rollback + insufficient (no intent).
        $clamp = DB::transaction(function () use ($userId, $requestedHits, $unitPrice, $batchJobNonce, $requestNonce) {
            $row = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['di']);
            if (!$row) {
                return ['hits' => 0, 'cost' => 0];
            }
            $di = (int) $row->di;
            $affordable = intdiv($di, $unitPrice);
            $hits = min($requestedHits, max(0, $affordable));
            if ($hits <= 0) {
                return ['hits' => 0, 'cost' => 0];
            }
            $cost = $unitPrice * $hits;
            DB::table('users')->where('id', $userId)
                ->update(['di' => DB::raw("di - {$cost}"), 'updated_at' => now()]);
            DB::table('lucky_batch_intents')->insert([
                'nonce'         => $batchJobNonce,
                'request_nonce' => $requestNonce,
                'user_id'       => $userId,
                'cost'          => $cost,
                'status'        => 'debited',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            return ['hits' => $hits, 'cost' => $cost];
        });

        $n = (int) $clamp['hits'];
        $totalBatchCost = (int) $clamp['cost'];
        $partial = $n < $requestedHits;

        if ($n <= 0) {
            // Nothing affordable — single unified insufficient response (S-ECO-4:
            // no vault-state leak; same message whatever the cause).
            $responseData['combo'][] = ['status' => 1, 'data' => null, 'error_message' => __('api_responses.insufficient')];
            $responseData['user_coins'] = (int) $user->di;
            $responseData['summary'] = ['total_win' => 0, 'max_single_win' => 0, 'total_win_count' => 0];
            // Signal a fully-unaffordable send so the controller returns a real error
            // (status 0 + insufficient message) instead of a success the app animates.
            $responseData['insufficient'] = true;
            return $responseData;
        }
        $user->di -= $totalBatchCost; // mirror

        // Build the clamped schedule (round-major, receivers in order).
        $schedule = [];
        for ($i = 0; $i < $n; $i++) {
            $schedule[] = $receiversIds[$i % $receiversCount];
        }

        $pool = app(PoolManager::class);

        // From here until batchSettle returns, the money state is READ-ONLY (the
        // committed upfront debit is the only effect), so any throw → full refund.
        try {
            // ── [D] PURE PHP DRAW ───────────────────────────────────────────────
            $ownerRate     = FairLuckSetting::getOwnerFeeRate();
            $ownerBps      = EconomySplitter::ownerBpsFor($ownerRate);
            $receiverBps   = EconomySplitter::ownerBpsFor($receiverFeeRate);
            $negativeLimit = FairLuckSetting::getVaultNegativeLimit();
            $ttlSeconds    = max(0, (int) FairLuckSetting::getByKey('fairluck_user_data_ttl_days', 90)) * 86_400;

            /** @var MultiplierTable $table */
            $table = app(MultiplierTable::class)->preload();
            $cfg   = $table->buildSelectConfig();

            // Beginner protection config (§4). enabled=0 by default → eligible stays
            // false and the EVAL never seeds a budget (zero hot-path cost).
            $bp = $this->resolveBeginnerProtection($table, $cfg, $userId, $unitPrice);

            // Read-only snapshot of vault + the user's evolving RTP/streak state.
            $userState = $pool->creditAndRead(0, 0, $userId);
            $evolvingVault    = (int) $userState->vault;
            $evolvingSpent    = (int) $userState->totalSpent;
            $evolvingReceived = (int) $userState->totalReceived;
            $evolvingStreak   = (int) $userState->consecutiveLosses;

            // Nominal per-unit split — kept ONLY as the legacy fallback carried in
            // `common` for any in-flight payload enqueued before this deploy. The
            // authoritative per-bet cuts are computed cumulatively in the loop below
            // so a sub-coin fee (e.g. 1% of 50 = 0.5) is never floored away.
            $split       = EconomySplitter::split($unitPrice, $ownerRate, $receiverFeeRate);
            $ownerCut    = $split->ownerCut;
            $receiverCut = $split->receiverCut;
            $netToVault  = $split->netToVault;

            $bets = [];
            $perBetMeta = [];
            // bp_remaining is consumed inside the EVAL; locally we only need to know
            // whether the boosted draw COULD apply for the advisory lock-step.
            $bpLocalRemaining = $bp['eligible'] ? (int) $bp['budget'] : 0;

            // EXACT decimal receiver fee per bet for the transaction log (mirrors the
            // owner's exactAppFee). SUM(receiver_fee) == receiverRate × turnover.
            $exactReceiverFee = $unitPrice * $receiverFeeRate;

            $receiverCharismaByUser = [];

            // Per-receiver EXACT decimal charisma share, accumulated across the
            // schedule. Charisma is CLIENT-SIDE (owner decision): this map is the
            // precomputed per-receiver increment shipped INSIDE the in-room gift
            // frame (ProcessLuckyGiftPostJob) so the mic-holder folds it into their
            // own seat state — the backend stores/computes nothing. It stays decimal
            // so sub-coin shares (e.g. 1% of 50 = 0.5) accumulate exactly on the
            // client. Rate is read from settings (getReceiverFeeRate) — no hardcode.
            // SEPARATE from $receiverCutByUser (the integer wallet credit), untouched.
            foreach ($schedule as $receiverId) {
                // Accrue this bet's EXACT decimal receiver fee onto the receiver's
                // charisma share (decimal, no flooring — see note above).
                $receiverCharismaByUser[$receiverId] = ($receiverCharismaByUser[$receiverId] ?? 0) + $exactReceiverFee;

                // Owner AND receiver fees are NO LONGER floored into per-bet integers
                // here: both shares stay inside the vault (netToVault = full unitPrice)
                // and are accrued EXACTLY in bps-coins by the batchSettle EVAL (sole
                // fee path — see §6′). This avoids the double-count that would arise
                // from also flooring them into netToVault, and is the ONLY mechanism
                // that protects sub-coin receiver fees at receiverRate = 1%.
                $betOwnerCut = 0;
                $betNetToVault = $unitPrice;

                $evolvingVault += $betNetToVault; // gate sees post-credit balance

                $pair = BeginnerProtection::drawPair(
                    $table, $cfg, $bp['boostedCfg'], $unitPrice,
                    $evolvingVault, $evolvingSpent, $evolvingReceived, $evolvingStreak
                );

                // Advisory choice: mirror the EVAL's boosted-while-remaining rule so
                // the locally-evolved vault/streak stay close to what the EVAL does.
                $useBoost = $bpLocalRemaining > 0;
                $requestedPayout = $useBoost ? $pair['boostedPayout'] : $pair['normalPayout'];
                $advisoryMult    = $useBoost ? $pair['boostedMult']    : $pair['normalMult'];

                $appliedLocal = 0;
                if ($requestedPayout > 0 && ($evolvingVault - $requestedPayout) >= -$negativeLimit) {
                    $appliedLocal = $requestedPayout;
                }
                $evolvingVault -= $appliedLocal;
                $evolvingSpent += $unitPrice;
                if ($appliedLocal > 0) {
                    $evolvingReceived += $appliedLocal;
                    $evolvingStreak = 0;
                    if ($useBoost) {
                        $over = max(0, $appliedLocal - $bp['expectedBoostReturn']);
                        $bpLocalRemaining = max(0, $bpLocalRemaining - $over);
                    }
                } else {
                    $evolvingStreak += 1;
                }

                $bets[] = [
                    'netToVault'          => $betNetToVault,
                    'ownerCut'            => $betOwnerCut,
                    'betAmount'           => $unitPrice,
                    'boostedPayout'       => $pair['boostedPayout'],
                    'normalPayout'        => $pair['normalPayout'],
                    'expectedBoostReturn' => $bp['expectedBoostReturn'],
                    'receiverId'          => $receiverId,
                ];
                $perBetMeta[] = [
                    'receiverId'      => $receiverId,
                    'multiplier'      => $advisoryMult,
                    'requestedPayout' => $requestedPayout,
                    'ownerCut'        => $betOwnerCut,
                    // EXACT decimal receiver fee for the log (was the floored integer);
                    // the receiver's WALLET credit comes from the EVAL realized map.
                    'receiverFee'     => $exactReceiverFee,
                    'netToVault'      => $betNetToVault,
                ];
            }

            // ── [E] MONEY WINDOW 2 — claim debited→settling, then EVAL ───────────
            $settlingClaim = DB::table('lucky_batch_intents')->where('nonce', $batchJobNonce)
                ->where('status', 'debited')
                ->update(['status' => 'settling', 'updated_at' => now()]);
            if ($settlingClaim !== 1) {
                throw new \RuntimeException('lucky batch intent already compensated by reconcile - settle aborted');
            }

            $batch = $pool->batchSettle($userId, $bets, $negativeLimit, $ttlSeconds, $batchJobNonce, $bp['eval'], $ownerBps, $receiverBps);
        } catch (\Throwable $e) {
            $this->handleBatchWindowFailure($pool, $userId, $totalBatchCost, $n, $batchJobNonce, $user, $e);
            throw $e;
        }

        $applied    = $batch->applied;
        $totalPaid  = (int) $batch->totalPaid;
        $vaultAfter = (int) $batch->vault;

        // Receiver wallet credit = the EXACT integer the EVAL realized out of the
        // vault for each receiver (whole bps-coins crossing 1.0). The sub-coin tail
        // persists in KEY_RECEIVER_FRACTION_BPS for the next batch — zero loss. This
        // is the SOLE source of the per-receiver credit (replaces the old floored
        // incrementalCut accumulation).
        $receiverCutByUser = array_map('intval', (array) $batch->receiverRealized);
        $totalReceiverCut  = array_sum($receiverCutByUser);

        // win_downgraded_by_drain telemetry parity.
        $downgraded = 0; $downgradedCoins = 0;
        foreach ($perBetMeta as $i => $meta) {
            if ($meta['requestedPayout'] > 0 && (int) ($applied[$i] ?? 0) === 0) {
                $downgraded++; $downgradedCoins += (int) $meta['requestedPayout'];
            }
        }
        if ($downgraded > 0) {
            Log::channel('lucky_gift')->warning('win_downgraded_by_drain', [
                'user_id' => $userId, 'path' => 'unified', 'nonce' => $batchJobNonce,
                'bets' => $n, 'downgraded_wins' => $downgraded,
                'downgraded_coins' => $downgradedCoins, 'vault_after' => $vaultAfter,
            ]);
        }

        // Mark settled (never flip a refunded/reconciled row back).
        try {
            $marked = DB::table('lucky_batch_intents')->where('nonce', $batchJobNonce)
                ->whereIn('status', ['settling', 'debited'])
                ->update(['status' => 'settled', 'total_paid' => $totalPaid, 'updated_at' => now()]);
            if ($marked !== 1) {
                Log::critical('MONEY-CRITICAL: batch settled but intent was already compensated - manual review required', [
                    'nonce' => $batchJobNonce, 'user_id' => $userId, 'total_paid' => $totalPaid,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('lucky_batch_intents settled-mark failed (commit-proof key still covers reconcile)', [
                'nonce' => $batchJobNonce, 'user_id' => $userId, 'error' => $e->getMessage(),
            ]);
        }

        // Win credit is applied EXCLUSIVELY in the post-job (idempotent). Mirror in
        // memory for the response only.
        $winCreditPending = $totalPaid;
        if ($totalPaid > 0) {
            $user->di += $totalPaid;
        }

        // ── [F] RESPONSE ASSEMBLY (mirrors getResponseData2 contract, G3) ───────
        $total_user_win = 0; $max_single_win = 0; $total_count_win = 0;
        $total_cashback_percentage = 0; $packedBets = [];
        $runningWalletBefore = (int) $userState->vault;
        $senderRunningBalance = $oldUserCoin;
        $commentMessage = $this->getCommentMessage($isToRoom, $receiverName, $number, $giftPrice);

        foreach ($perBetMeta as $i => $meta) {
            $appliedPayout = (int) ($applied[$i] ?? 0);
            $isWinner = $appliedPayout > 0;
            // Display/log multiplier MUST reflect the REALIZED payout (the Redis
            // EVAL's applied[]), not the PHP advisory draw (meta['multiplier']).
            // The two can diverge — most notably on a streak hard-floor forced win,
            // where the advisory draw is a loss (0) but the EVAL still pays out —
            // which previously showed the user "0 مردود" next to a large coin value.
            // Deriving the band from coins/grossBet keeps "N مردود" consistent with
            // the coins shown (and the transaction-log win_multiplier).
            $multiplier = ($isWinner && $unitPrice > 0) ? intdiv($appliedPayout, $unitPrice) : 0;
            $iterationWin = $appliedPayout;

            $betNetToVault = (int) ($meta['netToVault'] ?? $netToVault);
            $walletBeforeHit = $runningWalletBefore + $betNetToVault;
            $walletAfterHit  = $walletBeforeHit - $appliedPayout;
            $runningWalletBefore = $walletAfterHit;

            if ($isWinner) {
                $total_user_win += $iterationWin; $total_count_win++;
                if ($iterationWin > $max_single_win) { $max_single_win = $iterationWin; }
            }
            // Per-bet cuts ride along so the transaction log sums to the exact
            // owner/receiver totals (positions 2-4; older 2-element payloads fall
            // back to `common` in the post job). Position 2 is the EXACT decimal
            // owner fee (e.g. 50 × 0.01 = 0.50) so SUM(app_fee) reads exactly the
            // owner's rate of turnover — independent of the integer whole-coin
            // realization timing in Redis.
            $exactAppFee = $unitPrice * $ownerRate;
            // Position [3] is the EXACT decimal receiver fee (e.g. 50 × 0.01 = 0.50),
            // mirroring position [2]'s owner fee, so SUM(receiver_fee) reads exactly
            // the receiver's rate of turnover — independent of the integer whole-coin
            // realization timing in Redis. The decimal(12,2) column already holds it.
            $packedBets[] = [$appliedPayout, $multiplier, $exactAppFee, (float) ($meta['receiverFee'] ?? $exactReceiverFee), $betNetToVault];

            $message = ($isWinner && $multiplier > 1) ? $this->winnerMessage($multiplier) : null;
            $isPopular = $iterationWin >= $luckyGiftCoinsThreshold;
            if ($isPopular && $iterationWin > 0) {
                $this->sendPopularToStreamV2($userId, $user, $gift, $ownerId, $room, $multiplier, cashbackValue: $iterationWin);
            }
            $sendMessage = $message ?? '';
            $senderBalanceAfterHit = (int) ($senderRunningBalance - $unitPrice + $appliedPayout);

            $responseData['combo'][] = [
                'status' => 0,
                'data' => [
                    'win_coins' => (int) $iterationWin,
                    'is_win' => $isWinner,
                    'win_multiplier' => $isWinner ? $multiplier : 0,
                    'is_popular' => $isPopular,
                    'comment_message' => $commentMessage,
                    'winner_comment' => $sendMessage,
                ],
                'error_message' => '',
                'sender_balance_before' => (int) $senderRunningBalance,
                'sender_balance_after' => $senderBalanceAfterHit,
                'wallets_before' => ['lucky_wallet' => $walletBeforeHit],
                'wallets_after' => ['lucky_wallet' => $walletAfterHit],
            ];
            $senderRunningBalance = $senderBalanceAfterHit;
            $total_cashback_percentage += $multiplier;
        }

        // Partial combo (clamped) → unified insufficient tail entry (S-ECO-4).
        if ($partial) {
            $responseData['combo'][] = ['status' => 1, 'data' => null, 'error_message' => __('api_responses.insufficient')];
        }

        // ONE aggregated deduction log + one cashback log.
        $this->safeLogByType($userId, -abs($totalBatchCost), $oldUserCoin, UserCoinLogType::LUCKY_GIFT, $gift?->name);
        if ($total_user_win > 0) {
            $balanceBeforeCashbackLog = $oldUserCoin - ($n * $unitPrice);
            $this->safeLogByType($userId, $total_user_win, $balanceBeforeCashbackLog, UserCoinLogType::CASHBACK, null);
        }

        // Accounting from ACTUAL standing hits.
        $standingCount = $receiversCount > 0 ? intdiv($n + $receiversCount - 1, $receiversCount) : 0;
        $totalDiamond = $unitPrice * $n;
        $senderLevel = $this->safeSenderLevel($updateUserWhenSendGift, $user, $totalDiamond);
        $coinsForReceiverBase = $number * ($giftPrice * $receiverPayoutRate);
        $price = $coinsForReceiverBase * $receiversCount;
        $coinsForReceiver = $totalReceiverCut;
        $numberOut = $number * $standingCount;
        $coinsForOwnerPerReceiver = $unitPrice * $receiverFeeRate;
        $roomSessionToAdd = $coinsForOwnerPerReceiver * $n;

        $responseData['session'] = $room->session_string;
        $responseData['user_coins'] = (int) $user->di;
        $responseData['gift_num'] = $receiversCount * $numberOut;
        $responseData['total_price'] = (int) $totalDiamond;
        $responseData['charged_total'] = (int) $totalDiamond;
        $responseData['win_total'] = (int) $total_user_win;
        $responseData['cashback_percentage'] = $total_cashback_percentage;
        $responseData['total_user_win'] = $total_user_win;
        $responseData['gift_name'] = app()->getLocale() === 'ar' ? ($gift->name ?? $gift->e_name) : ($gift->e_name ?? $gift->name);
        $responseData['summary'] = [
            'total_win' => $total_user_win,
            'max_single_win' => $max_single_win,
            'total_win_count' => $total_count_win,
        ];
        $responseData['balances'] = [
            'sender' => ['before' => (int) $senderBalanceBefore, 'after' => (int) $user->di],
            'wallets' => [
                'before' => ['lucky_wallet' => (int) $userState->vault],
                'after' => ['lucky_wallet' => $vaultAfter],
            ],
        ];
        $responseData['total_pk'] = $coinsForReceiver;

        $user->syncOriginal();

        $this->dispatchPostJob([
            'user_id' => $userId,
            'room_id' => $roomId,
            'gift_id' => $giftId,
            'receivers_ids' => $receiversIds,
            'count' => $standingCount,
            'total_price' => (int) $totalDiamond,
            'total_user_win' => $total_user_win,
            'total_count_win' => $total_count_win,
            'coins_for_receiver' => $coinsForReceiver,
            'receiver_cut_map' => $receiverCutByUser,
            // EXACT decimal per-receiver charisma share (meter source). Separate
            // from receiver_cut_map (the integer wallet credit) so charisma accrues
            // fractionally without changing any wallet economics.
            'receiver_charisma_map' => $receiverCharismaByUser,
            'total_diamond' => $totalDiamond,
            'room_session' => $roomSessionToAdd,
            'sender_level' => $senderLevel,
            'charizma_status' => $room->charizma_status,
            'last_pk' => $room->lastPk ?? false,
            'room_type' => $room->type,
            'host_percentage' => $receiverPayoutRate,
            'room_boom_enabled' => true,
            'data' => $data,
            'number' => $numberOut,
            'price' => $price,
            'user_coins_before' => $oldUserCoin,
            'user_coins_after' => $user->di,
            'owner_id' => $ownerId,
            'job_nonce' => $batchJobNonce,
            'win_credit_pending' => $winCreditPending,
            'batch_intent' => true,
            'fairluck_packed' => [
                'common' => [
                    'user_id'              => $userId,
                    'gift_id'              => $gift->id,
                    'bet_amount'           => $unitPrice,
                    'app_fee'              => $ownerCut,
                    // EXACT decimal (legacy per-bet fallback only); the authoritative
                    // per-bet value rides in packedBets position [3].
                    'receiver_fee'         => $exactReceiverFee,
                    'net_to_vault'         => $netToVault,
                    'room_id'              => $roomId,
                    'sender_balance_start' => $oldUserCoin,
                    'vault_start'          => (int) $userState->vault,
                    'target_rtp'           => (float) ($cfg['targetRtp'] ?? 0.89),
                    'total_spent_start'    => (int) $userState->totalSpent,
                    'total_received_start' => (int) $userState->totalReceived,
                ],
                'bets' => $packedBets,
            ],
        ]);

        return $responseData;
    }

    /**
     * Resolve beginner-protection inputs for this combo. Panel-gated
     * (beginner_protection_enabled, default OFF) — when off, eligibility stays false
     * and the EVAL never seeds a budget, so the existence query never runs.
     *
     * @return array{eligible:bool, budget:int, expectedBoostReturn:int, boostedCfg:array, eval:array}
     */
    private function resolveBeginnerProtection(MultiplierTable $table, array $cfg, int $userId, int $unitPrice): array
    {
        $enabled = (bool) FairLuckSetting::getByKey('beginner_protection_enabled', false);
        $rtpBoost = (float) FairLuckSetting::getByKey('RTP_boost', 0.92);
        $rtpBoostBps = (int) round($rtpBoost * 10_000);

        $boostedShape = BeginnerProtection::boostedShape($cfg['multipliers'], $cfg['baseWeights']);
        $boostedCfg = $table->buildBoostedConfig($boostedShape, $rtpBoost);
        $expectedBoostReturn = intdiv($unitPrice * $rtpBoostBps, 10_000);

        $eligible = false;
        $budget = 0;
        if ($enabled) {
            $maxAgeDays = (int) FairLuckSetting::getByKey('beginner_max_age_days', 10);
            $row = User::where('id', $userId)->first(['created_at']);
            // created_at may arrive as a raw string (custom timestamp casting), so
            // normalise through Carbon before diffing — diffInDays() on a string fatals.
            $ageDays = $row && $row->created_at
                ? (int) \Carbon\Carbon::parse($row->created_at)->diffInDays(now())
                : PHP_INT_MAX;
            $hasRealPaidCharge = Charge::where('user_id', $userId)->exists();
            $eligible = BeginnerProtection::isEligible($hasRealPaidCharge, $ageDays, $maxAgeDays);
            $budget = (int) FairLuckSetting::getByKey('beginner_budget_coins', 0);
        }

        $smallestMult = !empty($cfg['multipliers']) ? (int) min($cfg['multipliers']) : 0;

        return [
            'eligible'            => $eligible,
            'budget'              => $budget,
            'expectedBoostReturn' => $expectedBoostReturn,
            'boostedCfg'          => $boostedCfg,
            'eval' => [
                'eligible'      => $eligible,
                'budget'        => $budget,
                'rtpBoostBps'   => $rtpBoostBps,
                'dailyCapTotal' => (int) FairLuckSetting::getByKey('beginner_global_daily_cap', 0),
                'hardFloor'     => MultiplierTable::STREAK_HARD_FLOOR,
                'smallestMult'  => $smallestMult,
            ],
        ];
    }

    /**
     * Failure handler for the draw/settle window after the upfront debit committed.
     * Moved VERBATIM from LuckyGiftService::handleBatchWindowFailure (G6): the
     * commit-proof key disambiguates EVAL-committed (no refund, credit deferred) from
     * EVAL-never-ran (full refund).
     */
    private function handleBatchWindowFailure(
        PoolManager $pool,
        int $userId,
        int $totalBatchCost,
        int $n,
        string $batchJobNonce,
        User $user,
        \Throwable $cause
    ): void {
        $proof = null;
        try {
            $proof = FairLuckWallet::vaultRedis()->get(PoolManager::intentProofKey($batchJobNonce));
        } catch (\Throwable $proofError) {
            Log::critical('MONEY-CRITICAL: batch window failed and commit-proof unreadable - left for lucky:reconcile-intents', [
                'user_id' => $userId, 'nonce' => $batchJobNonce, 'total_batch_cost' => $totalBatchCost,
                'bets' => $n, 'cause' => $cause->getMessage(), 'proof_error' => $proofError->getMessage(),
            ]);
            return; // intent stays 'debited' — reconcile decides with the proof key
        }

        if ($proof !== null && $proof !== false) {
            try {
                DB::table('lucky_batch_intents')->where('nonce', $batchJobNonce)
                    ->whereIn('status', ['debited', 'settling'])
                    ->update(['status' => 'settled', 'total_paid' => (int) $proof, 'updated_at' => now()]);
            } catch (\Throwable $markError) {
                Log::warning('lucky_batch_intents settled-mark failed in failure handler', [
                    'nonce' => $batchJobNonce, 'error' => $markError->getMessage(),
                ]);
            }
            Log::critical('MONEY-CRITICAL: batch settled but request failed after EVAL - win deferred to lucky:reconcile-intents', [
                'user_id' => $userId, 'nonce' => $batchJobNonce, 'total_batch_cost' => $totalBatchCost,
                'total_paid' => (int) $proof, 'bets' => $n, 'cause' => $cause->getMessage(),
            ]);
            return;
        }

        // EVAL never ran → refund the upfront debit in full (claim-first).
        try {
            $refunded = DB::transaction(function () use ($userId, $totalBatchCost, $batchJobNonce) {
                $claimed = DB::table('lucky_batch_intents')->where('nonce', $batchJobNonce)
                    ->whereIn('status', ['debited', 'settling'])
                    ->update(['status' => 'refunded', 'updated_at' => now()]);
                if ($claimed === 1) {
                    DB::table('users')->where('id', $userId)->increment('di', $totalBatchCost, ['updated_at' => now()]);
                }
                return $claimed === 1;
            });
            if ($refunded) {
                $user->di += $totalBatchCost;
            }
            Log::error('FairLuck batch draw/settle FAILED after upfront debit - refunded in full', [
                'user_id' => $userId, 'n' => $n, 'nonce' => $batchJobNonce, 'error' => $cause->getMessage(),
            ]);
        } catch (\Throwable $refundError) {
            Log::critical('MONEY-CRITICAL: batch refund FAILED - intent left for lucky:reconcile-intents', [
                'user_id' => $userId, 'nonce' => $batchJobNonce, 'total_batch_cost' => $totalBatchCost,
                'bets' => $n, 'cause' => $cause->getMessage(), 'refund_error' => $refundError->getMessage(),
            ]);
        }
    }

    // ── helpers (mirrored from LuckyGiftService, unchanged contracts) ──────────

    private function safeSenderLevel(UpdateUserWhenSendGift $updateUserWhenSendGift, User $user, $totalDiamond)
    {
        try {
            return $updateUserWhenSendGift->getSenderLevel($user->total_diamond_send, $totalDiamond, $user->sub_sender_level);
        } catch (\Throwable $e) {
            Log::warning('getSenderLevel failed after money commit - sender_level skipped (non-money)', [
                'user_id' => $user->id, 'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function safeLogByType(int $userId, float $amount, float $amountBefore, UserCoinLogType $type, ?string $itemName): void
    {
        try {
            UserCoinLogHelper::logByType($userId, $amount, $amountBefore, $type, $itemName);
        } catch (\Throwable $e) {
            Log::warning('Lucky gift coin-log dispatch failed (audit only, money already settled)', [
                'user_id' => $userId, 'type' => $type->value, 'amount' => $amount, 'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchPostJob(array $payload): void
    {
        $payload['job_nonce'] = $payload['job_nonce'] ?? (string) Str::uuid();
        try {
            \App\Jobs\ProcessLuckyGiftPostJob::dispatch($payload)->onQueue('gifts');
        } catch (\Throwable $e) {
            Log::error('ProcessLuckyGiftPostJob dispatch failed - running inline', [
                'user_id' => $payload['user_id'] ?? null, 'error' => $e->getMessage(),
            ]);
            try {
                (new \App\Jobs\ProcessLuckyGiftPostJob($payload))->handle();
            } catch (\Throwable $inner) {
                Log::critical('MONEY-CRITICAL: lucky gift post-processing LOST - payload kept for manual replay', [
                    'error' => $inner->getMessage(), 'payload' => $payload,
                ]);
            }
        }
    }

    private function getReceiverName(mixed $isToRoom, ?string $receiverName): string
    {
        return $isToRoom ? 'الغرفة' : ($receiverName ?? '');
    }

    private function getCommentMessage(mixed $isToRoom, ?string $receiverName, $number, $giftPrice): string
    {
        $to = $this->getReceiverName($isToRoom, $receiverName);
        return "{$number} x ارسل هدية حظ " . " قيمتها {$giftPrice} " . " الى {$to}";
    }

    private function winnerMessage(mixed $cashback_percentage): string
    {
        return "مبروووك .. كسبت " . $cashback_percentage . " ضعف قيمة الهدية";
    }

    private function getResponseData2($gift, $room, $user, $receiversIds, $receiverName)
    {
        if (!$room->relationLoaded('microphones')) {
            $room->load('microphones');
        }
        $microphones = $room->microphones;
        $positions = [];
        $missingReceivers = [];
        foreach ($receiversIds as $receiverId) {
            $mic = $microphones->firstWhere('user_id', $receiverId);
            if ($mic) {
                $positions[] = $mic->position;
            } else {
                $positions[] = -1;
                $missingReceivers[] = $receiverId;
            }
        }
        if (!empty($missingReceivers)) {
            Log::warning('Lucky gift: receivers without room_microphones records', [
                'room_id' => $room->id, 'missing_receiver_ids' => $missingReceivers,
            ]);
        }
        return [
            'gift_image' => $gift->img,
            'receiver_name' => $receiverName,
            'receivers_ids' => $receiversIds,
            'sender_id' => $user->id ?? 0,
            'sender_name' => $user->name ?? '',
            'sender_img' => $user->profile->avatar ?? '',
            'position' => $positions,
            'combo' => [],
        ];
    }

    private function sendPopularToStreamV2(mixed $userId, User $user, Gift $gift, mixed $ownerId, Room $room, mixed $cashback_percentage, $cashbackValue = 0): void
    {
        try {
            $streamData = [
                'user_id' => $userId,
                'user_image' => @$user->profile->avatar ?? '',
                'gift_image' => @$gift->img ?? '',
                'owner_id' => $ownerId,
                'user_name' => $user->name ?? '',
                'room_id' => $room->id,
                'percentage' => $cashback_percentage,
                'is_room_pass' => ($room->room_pass != null && $room->room_pass != ''),
                'gift_price' => $gift->price,
                'cache_value' => ceil($cashbackValue ?? 0.0),
                'room_name' => $room->room_name ?: '',
                'room_cover' => $room->room_cover ?? '',
                'room_background' => $room->final_room_image ?? '',
                'room_mode' => $room->mode,
                'room_uuid' => $room->owner?->uuid ?: 0,
                'room_owner_id' => $room->uid ?: 0,
                'is_password' => (bool) (@$room->room_pass),
                'room_type' => (@$room->type),
                // Unique id PER winning iteration. The client keys each banner
                // by this so two distinct wins in the same combo (even with an
                // identical coin value) can never be merged/deduped into one.
                'win_id' => uniqid('lw_', true),
            ];
            $this->sendToStreamLuckyGiftV2($streamData);
        } catch (\Throwable $e) {
            Log::warning('Lucky gift popular broadcast failed (non-critical)', [
                'user_id' => $userId, 'room_id' => $room->id ?? null, 'error' => $e->getMessage(),
            ]);
        }
    }
}
