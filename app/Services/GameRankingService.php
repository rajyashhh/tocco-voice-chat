<?php

namespace App\Services;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Games leaderboard — thin adapter over the unified RankingScoreService.
 *
 * The games rank was the original proven Redis-sorted-set implementation;
 * RankingScoreService now owns the generic ZINCRBY/ZREVRANGE/bucketKey logic
 * (section = 'games'). This class keeps the games-specific public API
 * (recordPlay / getTopPlayers / hasData / backfillFromDB, keyed by the int
 * `type` the games endpoints pass) so RankingRepository::getUserGameCoins and
 * the game:backfill-ranking command keep working unchanged, while adding the
 * hourly bucket via the unified service.
 *
 * Period boundaries use Common::timeZone() — the same clock the DB-fallback
 * ranking ranges (RankingRepository::getDateRange) are computed with.
 */
class GameRankingService
{
    private const SECTION = 'games';

    public function __construct(
        private readonly RankingScoreService $scores = new RankingScoreService()
    ) {}

    private function now(): Carbon
    {
        return Carbon::now(Common::timeZone());
    }

    /**
     * Map the games int `type` (0 hourly, 1 daily, 2 weekly, 3 monthly) to a period.
     */
    private function periodForType(int $type): string
    {
        return match ($type) {
            0       => 'hourly',
            2       => 'weekly',
            3       => 'monthly',
            default => 'daily',
        };
    }

    /**
     * Called on every win credit (coin_game_users.type = 1).
     * Credits the games section across all 4 buckets (hourly/daily/weekly/monthly).
     */
    public function recordPlay(int $userId, int $coins): void
    {
        if ($coins <= 0) return;

        $this->scores->add(self::SECTION, $userId, $coins);
    }

    /**
     * Get top N players for a given period.
     * Returns: [['user_id' => int, 'exp' => int], ...]
     */
    public function getTopPlayers(int $type, int $limit = 50): array
    {
        $rows = $this->scores->topN(self::SECTION, $this->periodForType($type), $limit);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'user_id' => (int) $row['member'],
                'exp'     => (int) $row['score'],
            ];
        }

        return $result;
    }

    /**
     * Check if Redis has data for the requested period.
     */
    public function hasData(int $type): bool
    {
        return $this->scores->hasData(self::SECTION, $this->periodForType($type));
    }

    /**
     * Rebuild a period's sorted set from the DB and swap it in atomically
     * (temp key + RENAME), so re-runs never double-count.
     *
     * Full past days come from coin_game_users_daily_aggregated (one row per
     * user/game/day — total_win is exactly SUM(coins) WHERE type=1, verified
     * bit-equal against the raw tables); only today is aggregated from the raw
     * live + archive rows. The old implementation scanned millions of raw
     * archive rows per call (~19s for a month) — this runs in milliseconds.
     */
    public function backfillFromDB(string $type = 'daily'): int
    {
        $now   = $this->now();
        $today = $now->copy()->startOfDay();

        // Map the legacy string $type to the unified period; 'hourly' supported too.
        $period = in_array($type, RankingScoreService::PERIODS, true) ? $type : 'daily';

        $periodStart = match ($period) {
            'hourly'  => $now->copy()->startOfHour(),
            'weekly'  => $now->copy()->startOfWeek(),
            'monthly' => $now->copy()->startOfMonth(),
            default   => $today->copy(),
        };

        $merged = [];

        // Past days — daily aggregate, up to its REAL coverage (MAX(date), not
        // "yesterday": the coin-game:aggregate cron runs at 07:00, so between
        // midnight and the run the aggregate lags one day).
        $maxAgg  = DB::table('coin_game_users_daily_aggregated')->max('date');
        $aggTo   = $maxAgg ? Carbon::parse($maxAgg, Common::timeZone()) : null;
        $rawFrom = $aggTo ? $aggTo->copy()->addDay()->startOfDay() : $periodStart->copy();
        if ($periodStart->gt($rawFrom)) {
            $rawFrom = $periodStart->copy();
        }

        // The hourly bucket is fully inside today, so never reads the daily aggregate.
        if ($period !== 'hourly' && $aggTo && $periodStart->toDateString() <= $aggTo->toDateString()) {
            $rows = DB::table('coin_game_users_daily_aggregated')
                ->select('user_id', DB::raw('SUM(total_win) as total'))
                ->whereBetween('date', [
                    $periodStart->toDateString(),
                    $aggTo->toDateString(),
                ])
                ->whereNotNull('user_id')
                ->where('user_id', '!=', 0)
                ->groupBy('user_id')
                ->get();
            foreach ($rows as $row) {
                $merged[$row->user_id] = ($merged[$row->user_id] ?? 0) + (int) $row->total;
            }
        }

        // Uncovered tail (raw rows). For hourly: just this hour.
        $rawStart = $period === 'hourly' ? $periodStart->copy() : $rawFrom;
        $rawEnd   = $period === 'hourly' ? $now->copy()->endOfHour() : $now->copy()->endOfDay();
        $bounds   = [$rawStart->toDateTimeString(), $rawEnd->toDateTimeString()];
        foreach (['coin_game_users', 'coin_game_users_archive'] as $table) {
            $rows = DB::table($table)
                ->select('user_id', DB::raw('SUM(coins) as total'))
                ->where('type', 1)
                ->whereBetween('created_at', $bounds)
                ->whereNotNull('user_id')
                ->where('user_id', '!=', 0)
                ->groupBy('user_id')
                ->get();
            foreach ($rows as $row) {
                $merged[$row->user_id] = ($merged[$row->user_id] ?? 0) + (int) $row->total;
            }
        }

        return $this->scores->replaceBucket(self::SECTION, $period, $merged);
    }
}
