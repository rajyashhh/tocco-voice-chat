<?php

namespace App\Services\FairLuck\V7;

use Illuminate\Support\Facades\Redis;

/**
 * UserRTPTracker V7: Integer-based RTP tracking in Redis.
 *
 * Uses HINCRBY (integer) instead of HINCRBYFLOAT to prevent float drift.
 * All coin values are integers.
 */
class UserRTPTracker
{
    private const KEY_PREFIX = 'fairluck:V7:user:';

    public function getStats(int $userId): object
    {
        // Validate TTL without refreshing activity timestamp
        // (activity is only recorded in recordBet, not on reads)
        $ttlManager = app(UserDataTTLManager::class);
        if (!$ttlManager->validateUserData($userId)) {
            return (object) [
                'total_spent' => 0,
                'total_received' => 0,
                'bet_count' => 0,
                'win_count' => 0,
                'first_bet_ts' => 0,
            ];
        }

        $key = self::KEY_PREFIX . $userId;
        $data = Redis::hgetall($key);

        return (object) [
            'total_spent' => (int) ($data['total_spent'] ?? 0),
            'total_received' => (int) ($data['total_received'] ?? 0),
            'bet_count' => (int) ($data['bet_count'] ?? 0),
            'win_count' => (int) ($data['win_count'] ?? 0),
            'first_bet_ts' => (int) ($data['first_bet_ts'] ?? 0),
            'consecutive_losses' => (int) ($data['consecutive_losses'] ?? 0),
        ];
    }

    public function getRTP(int $userId): float
    {
        $stats = $this->getStats($userId);
        if ($stats->total_spent <= 0) {
            return 0.0;
        }
        return $stats->total_received / $stats->total_spent;
    }

    /**
     * Record a bet result using integer arithmetic.
     *
     * @param int  $spent    Gross bet amount (what user paid)
     * @param int  $received Sender payout (what user got back)
     * @param bool $isWinner Whether user won
     */
    public function recordBet(int $userId, int $spent, int $received, bool $isWinner): void
    {
        $key = self::KEY_PREFIX . $userId;

        Redis::hincrby($key, 'total_spent', $spent);
        if ($received > 0) {
            Redis::hincrby($key, 'total_received', $received);
        }
        Redis::hincrby($key, 'bet_count', 1);
        if ($isWinner) {
            Redis::hincrby($key, 'win_count', 1);
            Redis::hset($key, 'consecutive_losses', 0); // Reset on win
        } else {
            Redis::hincrby($key, 'consecutive_losses', 1); // Increment on loss
        }

        if (!Redis::hexists($key, 'first_bet_ts')) {
            Redis::hset($key, 'first_bet_ts', time());
        }

        $ttlManager = app(UserDataTTLManager::class);
        $ttlManager->recordActivity($userId);
    }
}
