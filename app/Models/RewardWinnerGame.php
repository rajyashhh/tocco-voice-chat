<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RewardWinnerGame extends Model
{
    use HasFactory;

    public const REWARD_MAP_CACHE_KEY = 'reward_winner_games_map';

    /**
     * rank => reward_coins for the whole (tiny, static) table, cached long-lived
     * and invalidated by RewardWinnerGameObserver on any write. gameEnd reads a
     * single rank out of this map instead of querying per game-end. Stored as a
     * plain array (rank => int) so the cache holds no Eloquent models.
     */
    public static function rewardMap(): array
    {
        return Cache::rememberForever(self::REWARD_MAP_CACHE_KEY, function () {
            return self::query()
                ->pluck('reward_coins', 'rank')
                ->map(fn($coins) => (int) $coins)
                ->toArray();
        });
    }
}
