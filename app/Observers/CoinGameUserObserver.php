<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CoinGameUserObserver
{
    /**
     * Invalidate Games ranking cache whenever a game play is recorded.
     * This makes the daily ranking update instantly after each game.
     * Weekly/monthly have a 5-min TTL safety net.
     */
    public function created($model): void
    {
        // The DB-fallback ranking cache is keyed by 'game_rank_db:{type}:{startOfPeriodDate}'
        // (see RankingRepository::getUserGameCoins). The previous keys here ('game_rank:{type}:...')
        // matched neither that cache nor the Redis sorted sets, so invalidation never took effect.
        // Note: the real-time path is the Redis sorted set (GameRankingService), which is updated
        // by ZINCRBY on every play and needs no forget. This only clears the DB-fallback snapshot.
        $today = Carbon::today()->toDateString();

        // Hourly (type 0) and daily (type 1) both bucket by the day's startOfDay date string.
        Cache::forget('game_rank_db:0:' . $today);
        Cache::forget('game_rank_db:1:' . $today);

        // Weekly (type 2) is keyed by startOfWeek date.
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        Cache::forget('game_rank_db:2:' . $weekStart);

        // Monthly (type 3) is keyed by startOfMonth date.
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        Cache::forget('game_rank_db:3:' . $monthStart);
    }
}
