<?php

namespace App\Services\FairLuck;

use App\Models\UserLuckProfile;
use Illuminate\Support\Facades\DB;


class ProfileManager
{
    /**
     * Get or create user luck profile.
     */
    public function getProfile(int $userId): UserLuckProfile
    {
        try {
            return UserLuckProfile::firstOrCreate(
                ['user_id' => $userId],
                [
                    'total_bets' => 0,
                    'total_profit' => 0,
                    'bet_count' => 0,
                    'win_count' => 0,
                    'current_deviation' => 0,
                    'is_legacy_user' => false,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate key (1062): another concurrent request created it first.
            // Re-fetch; firstOrCreate as a final guard so we never return null
            // (the method signature is non-nullable UserLuckProfile).
            if (($e->errorInfo[1] ?? null) === 1062) {
                return UserLuckProfile::firstOrCreate(
                    ['user_id' => $userId],
                    [
                        'total_bets' => 0,
                        'total_profit' => 0,
                        'bet_count' => 0,
                        'win_count' => 0,
                        'current_deviation' => 0,
                        'is_legacy_user' => false,
                    ]
                );
            }
            throw $e;
        }
    }

    /**
     * Update profile stats after a bet.
     *
     * Persisted via an atomic, lock-free UPDATE (increment) rather than the old
     * read-then-save, which dropped concurrent updates (lost-update race) and held
     * the row across the read. Behaviour is identical, the write is now race-safe.
     */
    public function updateStats(UserLuckProfile $profile, float $betAmount, float $profitAmount, bool $isWinner, float $newDeviation)
    {
        $this->applyStatsAtomically($profile->user_id, $betAmount, $profitAmount, $isWinner, $newDeviation);
    }

    /**
     * Apply a WHOLE batch of bet stats as ONE atomic increment on the user's row.
     *
     * The post-job used to call applyStatsAtomically once per bet — up to 999
     * X-lock re-acquisitions on the same hot user_luck_profiles row inside the
     * money transaction, which widened the lock-hold window and deadlocked
     * against concurrent jobs of the same sender (verified 1213s on VM test).
     * One summed UPDATE keeps the exact same end state with a single short lock.
     */
    public function applyAggregatedStats(int $userId, float $totalBets, float $totalProfit, int $betCount, int $winCount, float $newDeviation): void
    {
        $values = [
            'total_bets'        => DB::raw('total_bets + ' . (float) $totalBets),
            'total_profit'      => DB::raw('total_profit + ' . (float) $totalProfit),
            'bet_count'         => DB::raw('bet_count + ' . (int) $betCount),
            'win_count'         => DB::raw('win_count + ' . (int) $winCount),
            'first_bet_at'      => DB::raw('COALESCE(first_bet_at, NOW())'),
            'current_deviation' => $newDeviation,
        ];

        $updated = UserLuckProfile::where('user_id', $userId)->update($values);

        if ($updated === 0) {
            // Row does not exist yet — create it (firstOrCreate guards the race).
            $profile = $this->getProfile($userId);
            UserLuckProfile::where('id', $profile->id)->update($values);
        }
    }

    /**
     * Atomic, lock-free per-user stats increment. Safe to call from the async
     * post job (Redis-authoritative path) or inline (legacy path).
     */
    public function applyStatsAtomically(int $userId, float $betAmount, float $profitAmount, bool $isWinner, float $newDeviation): void
    {
        $updated = UserLuckProfile::where('user_id', $userId)->update([
            'total_bets'        => DB::raw('total_bets + ' . (float) $betAmount),
            'total_profit'      => DB::raw('total_profit + ' . (float) $profitAmount),
            'bet_count'         => DB::raw('bet_count + 1'),
            'win_count'         => DB::raw('win_count + ' . ($isWinner ? 1 : 0)),
            'first_bet_at'      => DB::raw('COALESCE(first_bet_at, NOW())'),
            'current_deviation' => $newDeviation,
        ]);

        if ($updated === 0) {
            // Row does not exist yet — create it (firstOrCreate guards the race).
            $profile = $this->getProfile($userId);
            UserLuckProfile::where('id', $profile->id)->update([
                'total_bets'        => DB::raw('total_bets + ' . (float) $betAmount),
                'total_profit'      => DB::raw('total_profit + ' . (float) $profitAmount),
                'bet_count'         => DB::raw('bet_count + 1'),
                'win_count'         => DB::raw('win_count + ' . ($isWinner ? 1 : 0)),
                'first_bet_at'      => DB::raw('COALESCE(first_bet_at, NOW())'),
                'current_deviation' => $newDeviation,
            ]);
        }
    }
}
