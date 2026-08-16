<?php

namespace App\Observers;

use App\Models\RewardWinnerGame;
use Illuminate\Support\Facades\Cache;

class RewardWinnerGameObserver
{
    /**
     * Invalidate the cached rank => reward map (RewardWinnerGame::rewardMap)
     * whenever the admin edits the reward config, so gameEnd always credits the
     * current values.
     */
    public function saved(RewardWinnerGame $reward): void
    {
        Cache::forget(RewardWinnerGame::REWARD_MAP_CACHE_KEY);
    }

    public function deleted(RewardWinnerGame $reward): void
    {
        Cache::forget(RewardWinnerGame::REWARD_MAP_CACHE_KEY);
    }
}
