<?php

namespace App\Services;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * Unified real-time leaderboard backed by Redis Sorted Sets.
 *
 * Generalises the proven GameRankingService (games rank) pattern to every
 * ranking section. Each scoring event calls add() → O(log N) ZINCRBY on the
 * per-(section, period-bucket) key plus a self-expire (TTL renewed on every
 * write — no reset cron). Reads do ZREVRANGE 0..N-1 WITHSCORES; the caller
 * hydrates the returned member ids in a single WHERE id IN.
 *
 * Period boundaries use Common::timeZone() — the same clock the DB-fallback
 * ranges (RankingRepository::applyDateFilters) are computed with — and the
 * weekly bucket honours the white-label `week_start` panel setting.
 *
 * Key: rank:{section}:{period}:{bucket} -> ZSET(member, score)
 *   section: room|wealth|charm|games|agency|lucky
 *   period:  hourly|daily|weekly|monthly
 *   bucket:  hourly=Y-m-d-H  daily=Y-m-d  weekly=Y-m-d(startOfWeek)  monthly=Y-m
 *   member:  wealth/charm/lucky/games=user_id, agency=agency_id, room=roomowner_id
 */
class RankingScoreService
{
    public const SECTIONS = ['room', 'wealth', 'charm', 'games', 'agency', 'lucky'];

    public const PERIODS = ['hourly', 'daily', 'weekly', 'monthly'];

    /** TTL per period — renewed on every ZINCRBY so a live bucket never expires under it. */
    private const TTL = [
        'hourly'  => 10800,   // 3 hours
        'daily'   => 172800,  // 2 days
        'weekly'  => 691200,  // 8 days
        'monthly' => 2764800, // 32 days
    ];

    private function now(): Carbon
    {
        return Carbon::now(Common::timeZone());
    }

    public function weekStartDay(): int
    {
        $value = Common::getSettingValue('week_start');
        if (is_numeric($value)) {
            return ((int) $value) % 7;
        }

        $map = [
            'sunday' => Carbon::SUNDAY, 'monday' => Carbon::MONDAY, 'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY, 'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY, 'saturday' => Carbon::SATURDAY,
        ];

        return $map[strtolower((string) $value)] ?? Carbon::MONDAY;
    }

    /**
     * Canonical inclusive [start, end] window for a period in the configured
     * timezone — the SINGLE source of truth bucketToken() and the DB backfill
     * (RankingRepository::rankingPeriodWindow) BOTH derive from, so the window a
     * backfill SUMs always matches the bucket the live ZINCRBYs feed.
     *
     * Weekly end is derived from the week_start anchor (start + 7 days - 1µs), NOT
     * Carbon's argument-less endOfWeek() which keys off the global week-end day and
     * is misaligned for any non-Monday week_start.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodWindow(string $period): array
    {
        $now = $this->now();

        return match ($period) {
            'hourly'  => [$now->copy()->startOfHour(), $now->copy()->endOfHour()],
            'weekly'  => (function () use ($now) {
                $start = $now->copy()->startOfWeek($this->weekStartDay());
                return [$start, $start->copy()->addWeek()->subMicrosecond()];
            })(),
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default   => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * Bucket token for a period using the configured timezone (+ week_start for weekly).
     */
    public function bucketToken(string $period): string
    {
        $now = $this->now();

        return match ($period) {
            'hourly'  => $now->format('Y-m-d-H'),
            'weekly'  => $this->periodWindow('weekly')[0]->toDateString(),
            'monthly' => $now->format('Y-m'),
            default   => $now->toDateString(),
        };
    }

    /**
     * Full Redis key for a section/period in the CURRENT bucket.
     */
    public function bucketKey(string $section, string $period): string
    {
        return "rank:{$section}:{$period}:" . $this->bucketToken($period);
    }

    private function ttlFor(string $period): int
    {
        return self::TTL[$period] ?? self::TTL['daily'];
    }

    /**
     * Add points for a member across ALL 4 period buckets (atomic per ZINCRBY).
     * Best-effort: callers wrap in try/catch so a Redis failure never breaks the
     * scoring event (gift send etc).
     */
    public function add(string $section, int|string $member, $points): void
    {
        $points = (float) $points;
        if ($points == 0.0 || $member === null || $member === '' || $member === 0 || $member === '0') {
            return;
        }

        foreach (self::PERIODS as $period) {
            $key = $this->bucketKey($section, $period);
            Redis::zincrby($key, $points, $member);
            Redis::expire($key, $this->ttlFor($period));
        }
    }

    /**
     * Bulk variant of add(): apply many (section, member, points) increments in a
     * SINGLE Redis pipeline across all 4 period buckets, instead of one round-trip
     * pair per add(). Callers pre-aggregate per (section, member) so a multi-receiver
     * gift send (~256 rows) collapses to a handful of distinct increments and one
     * network flush — same end state as looping add(), a fraction of the round-trips.
     *
     * @param array<string, array<int|string, float>> $bySection [section => [member => points]]
     */
    public function addBatch(array $bySection): void
    {
        // Normalise + drop invalid members/zero scores up front (mirror add()'s guard)
        // so the pipeline only carries real work and we know which keys to touch.
        $clean = [];
        foreach ($bySection as $section => $members) {
            foreach ($members as $member => $points) {
                $points = (float) $points;
                if ($points == 0.0 || $member === null || $member === '' || (string) $member === '0') {
                    continue;
                }
                $clean[$section][$member] = ($clean[$section][$member] ?? 0.0) + $points;
            }
        }

        if (empty($clean)) {
            return;
        }

        Redis::pipeline(function ($pipe) use ($clean) {
            foreach ($clean as $section => $members) {
                foreach (self::PERIODS as $period) {
                    $key = $this->bucketKey($section, $period);
                    foreach ($members as $member => $points) {
                        $pipe->zincrby($key, $points, $member);
                    }
                    $pipe->expire($key, $this->ttlFor($period));
                }
            }
        });
    }

    /**
     * Top N members of a section/period: [['member' => string, 'score' => float], ...]
     * in descending score order. Empty array when the bucket has no data.
     */
    public function topN(string $section, string $period, int $limit = 10): array
    {
        $key = $this->bucketKey($section, $period);

        if (Redis::zcard($key) === 0) {
            return [];
        }

        // PhpRedis: pass ['withscores' => true] (NOT the string 'WITHSCORES', which is
        // silently ignored and returns members-only — then the foreach below would treat
        // the array index as the member and the userId as the score, inverting the board).
        $raw = Redis::zrevrange($key, 0, $limit - 1, ['withscores' => true]);

        $result = [];
        foreach ($raw as $member => $score) {
            $result[] = [
                'member' => (string) $member,
                'score'  => (float) $score,
            ];
        }

        return $result;
    }

    /**
     * One member's absolute score + 1-based rank (ZSCORE + ZREVRANK), regardless
     * of whether they are inside the top N. Returns ['score' => float, 'rank' => ?int]
     * with rank null when the member is absent.
     */
    public function scoreAndRank(string $section, string $period, int|string $member): array
    {
        $key  = $this->bucketKey($section, $period);
        $score = Redis::zscore($key, $member);

        if ($score === null || $score === false) {
            return ['score' => 0.0, 'rank' => null];
        }

        $rank = Redis::zrevrank($key, $member);

        return [
            'score' => (float) $score,
            'rank'  => $rank === null || $rank === false ? null : ((int) $rank) + 1,
        ];
    }

    public function hasData(string $section, string $period): bool
    {
        return Redis::zcard($this->bucketKey($section, $period)) > 0;
    }

    /**
     * Atomically replace a bucket from a precomputed [member => score] base map
     * (temp key + ZADD + RENAME) so re-runs never double-count.
     *
     * IMPORTANT: this overwrites the live key wholesale, so any live ZINCRBY that
     * lands during the caller's DB read→build gap is lost. The caller MUST scope
     * $base to rows up to a captured watermark and then call mergeTail() with the
     * rows that arrived AFTER that watermark to restore the dropped increments.
     * See RankingBackfillCommand::backfillOne.
     *
     * @param array<int|string, float> $base precomputed scores up to the watermark
     */
    public function replaceBucket(string $section, string $period, array $base): int
    {
        $key = $this->bucketKey($section, $period);

        $base = $this->filterScores($base);

        if (empty($base)) {
            Redis::del($key);
            return 0;
        }

        $tmp = $key . ':rebuild';
        Redis::del($tmp);
        Redis::pipeline(function ($pipe) use ($tmp, $base) {
            foreach ($base as $member => $score) {
                $pipe->zadd($tmp, (float) $score, $member);
            }
        });
        Redis::rename($tmp, $key);
        Redis::expire($key, $this->ttlFor($period));

        return count($base);
    }

    /**
     * Merge a watermark-tail map onto the live bucket with ZINCRBY (add, NOT set),
     * restoring the increments a preceding replaceBucket() RENAME dropped during the
     * read→build gap. ZINCRBY also preserves brand-new live writes that arrive
     * between the RENAME and this merge, so nothing is clobbered. Best-effort.
     *
     * @param array<int|string, float> $tail increments for rows after the watermark
     */
    public function mergeTail(string $section, string $period, array $tail): int
    {
        $tail = $this->filterScores($tail);
        if (empty($tail)) {
            return 0;
        }

        $key = $this->bucketKey($section, $period);

        Redis::pipeline(function ($pipe) use ($key, $tail) {
            foreach ($tail as $member => $score) {
                $pipe->zincrby($key, (float) $score, $member);
            }
        });
        Redis::expire($key, $this->ttlFor($period));

        return count($tail);
    }

    /**
     * Per-(section, period) incremental watermark, keyed by the CURRENT bucket token.
     *
     * Stored as the ISO-8601 timestamp (configured timezone) of the data the live
     * bucket has been built up to. The bucket-token in the key is what makes a
     * period rollover self-invalidating: a new month/day/week token has no stored
     * watermark, so the backfill transparently falls back to a one-time full base
     * rebuild for the fresh bucket, then rides the incremental tail from there.
     *
     * The watermark expires with the bucket it belongs to (same TTL), so a stale
     * watermark can never outlive its bucket and re-merge into a different period.
     */
    public function watermarkKey(string $section, string $period): string
    {
        return 'rank:watermark:' . $section . ':' . $period . ':' . $this->bucketToken($period);
    }

    /**
     * Load the stored incremental watermark for the CURRENT bucket, or null when
     * absent (fresh bucket / rollover / Redis flush) — the signal to do a one-time
     * full base rebuild instead of an incremental tail.
     */
    public function loadWatermark(string $section, string $period): ?Carbon
    {
        $raw = Redis::get($this->watermarkKey($section, $period));
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw, Common::timeZone());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Persist the incremental watermark for the CURRENT bucket with the bucket's TTL.
     */
    public function saveWatermark(string $section, string $period, Carbon $watermark): void
    {
        Redis::setex(
            $this->watermarkKey($section, $period),
            $this->ttlFor($period),
            $watermark->copy()->setTimezone(Common::timeZone())->toIso8601String()
        );
    }

    /**
     * Authoritative cumulative score snapshot for the CURRENT bucket, keyed by token.
     *
     * This is the accumulator that makes the monthly backfill incremental WITHOUT
     * losing the overwrite-correctness of replaceBucket(): instead of re-SUMming the
     * whole month from the DB every cycle (the 3024-timeout source), we keep the
     * DB-authoritative [member => score] map as of the last watermark here, add only
     * the small (prevWatermark, now] delta to it, then replaceBucket() the result.
     * The RENAME-overwrite still zeroes any concurrent live-ZINCRBY drift, so the
     * bucket equals the full-scan SUM — but the DB only ever reads ~1 cycle of rows.
     *
     * Expires with its bucket (same TTL), so a new month/flush starts from empty.
     *
     * @return array<int|string, float>
     */
    public function loadSnapshot(string $section, string $period): array
    {
        $raw = Redis::hgetall($this->snapshotKey($section, $period));
        if (!is_array($raw) || empty($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $member => $score) {
            $out[$member] = (float) $score;
        }

        return $out;
    }

    public function snapshotKey(string $section, string $period): string
    {
        return 'rank:snapshot:' . $section . ':' . $period . ':' . $this->bucketToken($period);
    }

    /**
     * Persist the authoritative cumulative snapshot for the CURRENT bucket (atomic
     * temp-key + RENAME so a reader never sees a half-written map) with the bucket TTL.
     *
     * @param array<int|string, float> $snapshot
     */
    public function saveSnapshot(string $section, string $period, array $snapshot): void
    {
        $snapshot = $this->filterScores($snapshot);
        $key = $this->snapshotKey($section, $period);

        if (empty($snapshot)) {
            Redis::del($key);
            return;
        }

        $tmp = $key . ':rebuild';
        Redis::del($tmp);
        Redis::pipeline(function ($pipe) use ($tmp, $snapshot) {
            foreach ($snapshot as $member => $score) {
                $pipe->hset($tmp, (string) $member, (float) $score);
            }
        });
        Redis::rename($tmp, $key);
        Redis::expire($key, $this->ttlFor($period));
    }

    /**
     * Mirror the add() guard: drop zero scores AND invalid members (null/''/0/'0')
     * so an unguarded backfill source can never inject a bogus member.
     *
     * @param array<int|string, float> $scores
     * @return array<int|string, float>
     */
    private function filterScores(array $scores): array
    {
        return array_filter(
            $scores,
            fn ($s, $m) => (float) $s != 0.0 && $m !== null && $m !== '' && (string) $m !== '0',
            ARRAY_FILTER_USE_BOTH
        );
    }
}
