<?php

namespace App\Console\Commands;

use App\Repositories\RankingRepository;
use App\Services\GameRankingService;
use App\Services\RankingScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rebuild the CURRENT bucket of the unified Redis ranking from the DB
 * source-of-truth. Safety-net for the real-time RankingScoreService::add() path:
 * runs once on launch and every 15 min (Kernel) so the sorted sets self-heal
 * after a Redis flush/deploy and never drift for long.
 *
 * Each (section, period) is rebuilt atomically (temp key + RENAME via
 * RankingScoreService::replaceBucket) so a re-run never double-counts. ONLY the
 * current bucket is rebuilt — past buckets self-expire by TTL.
 *
 * Sources (mirror the live write path exactly):
 *   wealth → SUM(gift_logs.giftPrice WHERE not lucky) GROUP BY sender_id
 *            + SUM(fair_luck_transactions.bet_amount) GROUP BY user_id  (lucky turnover)
 *   charm  → SUM(gift_logs.giftPrice) GROUP BY receiver_id
 *   room   → SUM(gift_logs.giftPrice) GROUP BY roomowner_id
 *   agency → SUM(gift_logs.giftPrice) GROUP BY agency_id
 *   lucky  → SUM(fair_luck_transactions.bet_amount) GROUP BY user_id  (SPENDING/turnover)
 *   games  → GameRankingService::backfillFromDB (daily aggregate + raw tail)
 */
class RankingBackfillCommand extends Command
{
    protected $signature = 'ranking:backfill {section?} {period?}';

    protected $description = 'Rebuild the current-bucket unified Redis ranking sets from the DB (safety-net for RankingScoreService).';

    /** gift-fed section → gift_logs column. */
    private const GIFT_SECTION_COLUMN = [
        'wealth' => 'sender_id',
        'charm'  => 'receiver_id',
        'room'   => 'roomowner_id',
        'agency' => 'agency_id',
    ];

    public function handle(
        RankingRepository $repo,
        RankingScoreService $scores,
        GameRankingService $games
    ): int {
        $sections = $this->argument('section')
            ? [$this->argument('section')]
            : RankingScoreService::SECTIONS;

        $periods = $this->argument('period')
            ? [$this->argument('period')]
            : RankingScoreService::PERIODS;

        $total = 0;
        foreach ($sections as $section) {
            if (!in_array($section, RankingScoreService::SECTIONS, true)) {
                $this->warn("Unknown section: {$section}");
                continue;
            }

            foreach ($periods as $period) {
                if (!in_array($period, RankingScoreService::PERIODS, true)) {
                    $this->warn("Unknown period: {$period}");
                    continue;
                }

                try {
                    $count = $this->backfillOne($repo, $scores, $games, $section, $period);
                    $total += $count;
                    $this->info("ranking:backfill {$section}/{$period} → {$count} members");
                } catch (\Throwable $e) {
                    // One bad (section, period) must not abort the whole safety-net run.
                    Log::error('ranking:backfill failed', [
                        'section' => $section,
                        'period'  => $period,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->warn("ranking:backfill {$section}/{$period} FAILED: {$e->getMessage()}");
                }
            }
        }

        $this->info("ranking:backfill done — {$total} total members across rebuilt buckets.");

        return self::SUCCESS;
    }

    private function backfillOne(
        RankingRepository $repo,
        RankingScoreService $scores,
        GameRankingService $games,
        string $section,
        string $period
    ): int {
        if ($section === 'games') {
            // Games owns its own optimized DB rebuild (daily aggregate + raw tail).
            return $games->backfillFromDB($period);
        }

        // Watermark/tail rebuild — gap-safe against concurrent live ZINCRBYs.
        //
        // The atomic build-then-RENAME in replaceBucket() overwrites the live key,
        // so every gift/lucky write that lands during the DB read→build→rename gap
        // would be silently dropped. To recover them:
        //   1. capture a watermark BEFORE the base read,
        //   2. build the base from [windowStart, watermark] (inclusive) and RENAME,
        //   3. re-read the DB tail created_at > watermark — exactly the rows that
        //      wrote to the live key during the gap — and ZINCRBY them back.
        // The tail is read AFTER the RENAME so it sees the full gap; the strict ">"
        // vs the base's inclusive "<=" makes the two windows disjoint (no double-count).
        [$windowStart] = $repo->rankingPeriodWindow($period);
        $watermark = \Carbon\Carbon::now(\App\Helpers\Common::timeZone());

        // Per-section SUM sources (base = [from, to] inclusive; delta = (from, to]).
        // Defining them once guarantees the incremental delta is the SAME composition
        // as the full base — critical for wealth, which sums normal giftPrice + lucky
        // turnover, so the incremental ZINCRBY can never drift from the full-scan value.
        if ($section === 'lucky') {
            $base = fn ($from, $to) => $repo->sumLuckyScoresBetween($from, $to);
            $delta = fn ($from, $to) => $repo->sumLuckyScoresInRange($from, $to);
            $tailAfter = fn ($from) => $repo->sumLuckyScoresAfter($from);
        } elseif ($section === 'wealth') {
            // Wealth(sender) = normal-gift giftPrice (lucky EXCLUDED — the lucky
            // giftPrice is the host cut, not the sender's spend) + lucky TURNOVER
            // (fair_luck_transactions.bet_amount). IDENTICAL composition to the live
            // path: recordGiftRankings adds normal giftPrice, ProcessLuckyGiftPostJob
            // adds the same bet_amount turnover.
            $column = self::GIFT_SECTION_COLUMN['wealth'];
            $base = fn ($from, $to) => $repo->mergeScoreMaps(
                $repo->sumGiftScoresBetween($column, $from, $to, true),
                $repo->sumLuckyScoresBetween($from, $to)
            );
            $delta = fn ($from, $to) => $repo->mergeScoreMaps(
                $repo->sumGiftScoresInRange($column, $from, $to, true),
                $repo->sumLuckyScoresInRange($from, $to)
            );
            $tailAfter = fn ($from) => $repo->mergeScoreMaps(
                $repo->sumGiftScoresAfter($column, $from, true),
                $repo->sumLuckyScoresAfter($from)
            );
        } else {
            $column = self::GIFT_SECTION_COLUMN[$section];
            $base = fn ($from, $to) => $repo->sumGiftScoresBetween($column, $from, $to);
            $delta = fn ($from, $to) => $repo->sumGiftScoresInRange($column, $from, $to);
            $tailAfter = fn ($from) => $repo->sumGiftScoresAfter($column, $from);
        }

        // INCREMENTAL MONTHLY PATH (the 3024-timeout fix).
        //
        // The monthly window spans ~30 days (~11.5M fair_luck_transactions rows), so
        // re-SUMming the full month every 15 min is what produces the MySQL 3024
        // timeouts. The hourly/daily/weekly windows are small, so they keep the proven
        // full base+tail rebuild each cycle (cheap, self-heals instantly).
        //
        // For monthly we keep the SAME overwrite-correctness (replaceBucket RENAME
        // zeroes any concurrent live-ZINCRBY drift) but feed it a DB-authoritative
        // [member => score] ACCUMULATOR instead of a fresh full-month SUM:
        //
        //   snapshot(t-1)  = DB SUM of [month-start, prevWatermark]  (stored in Redis)
        //   delta          = DB SUM of (prevWatermark, watermark]     (~1 cycle of rows)
        //   snapshot(t)    = snapshot(t-1) + delta                    (== full-month SUM
        //                                                              up to watermark)
        //   replaceBucket(snapshot(t)); mergeTail(gap > watermark); save snapshot(t)
        //
        // Because snapshot(t) is provably the full-scan SUM [month-start, watermark]
        // (the base case is the full SUM; each step adds a disjoint (prev, wm] delta),
        // the RENAME writes EXACTLY what the old full-month replaceBucket wrote — only
        // the DB read shrank from 30 days to ~15 min. The gap tail + RENAME-overwrite
        // semantics are untouched, so concurrent live writes behave identically.
        //
        // A full base rebuild happens ONCE per bucket: month rollover (new Y-m token →
        // no snapshot), Redis flush (snapshot/watermark gone), or a >24h-stale
        // watermark (cron gap → re-base rather than trust a huge missed delta).
        if ($period === 'monthly') {
            $prev = $scores->loadWatermark($section, $period);
            $snapshot = $scores->loadSnapshot($section, $period);

            $usable = $prev !== null
                && !empty($snapshot)
                && $prev->gte($windowStart)                       // not from a previous month
                && $prev->gte($watermark->copy()->subDay());      // not >24h behind

            if ($usable) {
                // Incremental: accumulate only the (prevWatermark, watermark] delta.
                $snapshot = $repo->mergeScoreMaps($snapshot, $delta($prev, $watermark));
            } else {
                // One-time full base rebuild for this bucket (rollover / flush / stale).
                $snapshot = $base($windowStart, $watermark);
            }

            // Overwrite the live bucket with the authoritative accumulator (drops any
            // concurrent live-ZINCRBY drift), then restore the post-watermark gap.
            $count = $scores->replaceBucket($section, $period, $snapshot);
            $scores->mergeTail($section, $period, $tailAfter($watermark));

            // Persist the accumulator + watermark AFTER the overwrite commits. A crash
            // before this just leaves the prior snapshot/watermark → next cycle redoes
            // the same disjoint delta (mergeScoreMaps is idempotent on the stored base,
            // never additive across runs), so no double-count.
            $scores->saveSnapshot($section, $period, $snapshot);
            $scores->saveWatermark($section, $period, $watermark);

            return $count;
        }

        // hourly/daily/weekly: full base + gap tail every cycle (small windows).
        $count = $scores->replaceBucket($section, $period, $base($windowStart, $watermark));

        $scores->mergeTail($section, $period, $tailAfter($watermark));

        return $count;
    }
}
