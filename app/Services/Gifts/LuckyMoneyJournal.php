<?php

namespace App\Services\Gifts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Claim-first compensation journal for the lucky-gift SERIAL path.
 *
 * Rule (exactly-once under ambiguous failures): the journal row is inserted
 * FIRST (autocommit), then the balance mutation happens ONLY inside a tx that
 * conditionally claims the row (pending → applied). A lost connection after
 * commit leaves the row claimed, so any retry (inline or by the reconcile
 * command) finds 0 claimable rows and never double-applies. A bare increment
 * is never attempted before the row exists.
 */
class LuckyMoneyJournal
{
    public const TYPE_BET_REFUND = 'bet_refund';
    public const TYPE_WIN_CREDIT = 'win_credit';

    /** Best-effort fallback record when the journal table itself is unreachable. */
    public const REDIS_FALLBACK_LIST = 'lucky:refund_failures:fallback';

    /**
     * Journal a compensation and try to apply it immediately.
     *
     * @return bool true if the amount was credited to users.di in THIS call.
     *              false means the row stays pending (reconcile will retry) or,
     *              in the worst case, only the fallback record exists.
     */
    public static function journalAndApply(
        string $type,
        int $userId,
        int $amount,
        $giftId = null,
        $roomId = null,
        ?string $reason = null
    ): bool {
        if ($amount <= 0) {
            return true;
        }

        try {
            $rowId = DB::table('lucky_refund_failures')->insertGetId([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'gift_id' => $giftId,
                'room_id' => $roomId,
                'reason' => $reason !== null ? mb_substr($reason, 0, 500) : null,
                'state' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $insertError) {
            // The journal shares the failure domain with the credit itself —
            // record everything needed for replay in Redis AND the log, then stop
            // (no bare increment: it could commit ambiguously with no claim).
            Log::critical('MONEY-CRITICAL: lucky journal INSERT failed - compensation recorded to fallback only', [
                'type' => $type,
                'user_id' => $userId,
                'amount' => $amount,
                'gift_id' => $giftId,
                'room_id' => $roomId,
                'reason' => $reason,
                'error' => $insertError->getMessage(),
            ]);
            try {
                \App\Models\FairLuckWallet::vaultRedis()->rpush(self::REDIS_FALLBACK_LIST, json_encode([
                    'type' => $type, 'user_id' => $userId, 'amount' => $amount,
                    'gift_id' => $giftId, 'room_id' => $roomId, 'reason' => $reason, 'ts' => time(),
                    // Unique per-entry token so two same-second/same-amount credits
                    // never collide on content (the replay drain keys on this).
                    'uid' => uniqid('', true),
                ]));
            } catch (\Throwable $e) {
                // Both stores down — the critical log above is the last trace.
            }
            return false;
        }

        return self::applyClaimed($rowId, $userId, $amount);
    }

    /**
     * Claim the row (pending → applied) and credit the user in ONE transaction.
     */
    public static function applyClaimed(int $rowId, int $userId, int $amount): bool
    {
        try {
            return DB::transaction(function () use ($rowId, $userId, $amount) {
                $claimed = DB::table('lucky_refund_failures')
                    ->where('id', $rowId)
                    ->where('state', 'pending')
                    ->update(['state' => 'applied', 'updated_at' => now()]);
                if ($claimed === 1) {
                    DB::table('users')->where('id', $userId)
                        ->increment('di', $amount, ['updated_at' => now()]);
                }
                return $claimed === 1;
            });
        } catch (\Throwable $e) {
            Log::error('Lucky journal claim-tx failed - row stays pending for lucky:reconcile-intents', [
                'row_id' => $rowId, 'user_id' => $userId, 'amount' => $amount, 'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retry every claimable pending row (used by lucky:reconcile-intents).
     *
     * @return int rows applied this run
     */
    public static function retryPending(int $olderThanSeconds = 30, int $limit = 500): int
    {
        $rows = DB::table('lucky_refund_failures')
            ->where('state', 'pending')
            ->where('created_at', '<', now()->subSeconds($olderThanSeconds))
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'user_id', 'amount', 'type']);

        $applied = 0;
        foreach ($rows as $row) {
            if (self::applyClaimed((int) $row->id, (int) $row->user_id, (int) $row->amount)) {
                $applied++;
                Log::warning('lucky:reconcile-intents applied pending lucky compensation', [
                    'row_id' => $row->id, 'type' => $row->type,
                    'user_id' => $row->user_id, 'amount' => $row->amount,
                ]);
            }
        }

        return $applied;
    }
}
