<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Agency;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\helper\TimeHelper;
use App\Models\GiftRanking;
use App\Models\CoinGameUser;
use App\Models\CoinGameUserAll;
use App\Models\CoinGameUserMerged;
use Illuminate\Support\Facades\DB;
use App\Models\CoinGameUserArchive;
use Illuminate\Database\Eloquent\Model;
use App\Models\CoinGameUserMergedMonthly;

class RankingRepository
{
    /**
     * Hydrate a unified-ranking topN result (RankingScoreService::topN) into
     * GiftLog-shaped collection items the existing RankingService::transformData
     * + prepareResponse pipeline already consume, preserving the /api/ranking
     * response contract.
     *
     * member ids are user ids for wealth|charm|room|games|lucky (room member is
     * roomowner_id, itself a user id). One WHERE id IN (<=10) with a limited
     * eager-load, then re-ordered to the Redis (score-desc) order. Cached briefly
     * (the set is identical for every requester).
     *
     * @param array<int, array{member:string, score:float}> $topN
     * @return \Illuminate\Support\Collection of GiftLog with ->exp and ->{relation}
     */
    public function hydrateRanking(array $topN, string $relation, int $class): \Illuminate\Support\Collection
    {
        if (empty($topN)) {
            return collect();
        }

        $ids = array_map(fn ($r) => (int) $r['member'], $topN);
        $signature = implode(',', $ids);
        $cacheKey = 'rank:hydrate:' . $class . ':' . md5($signature);

        $users = \Cache::remember($cacheKey, 20, function () use ($ids, $class) {
            return User::query()
                ->whereIn('id', $ids)
                ->with($this->hydrateUserRelations($class))
                ->get()
                ->keyBy('id');
        });

        $result = collect();
        foreach ($topN as $row) {
            $user = $users[(int) $row['member']] ?? null;
            if (!$user) {
                continue;
            }

            $item = new GiftLog();
            $item->user_id = (int) $row['member'];
            $item->exp = (float) $row['score'];
            $item->setRelation($relation, $user);
            $result->push($item);
        }

        return $result;
    }

    /**
     * Hydrate an agency-ranking topN (member = agency_id, score = total gifts) into
     * the GiftRanking-shaped items NewAgencyRankingResource consumes:
     *   ->total_gifts = Redis score, ->ranker = Agency (with owner).
     * One WHERE id IN (<=10) with the owner eager-loaded, re-ordered to Redis
     * (score-desc) order. Cached briefly (identical for every requester).
     *
     * Returned as a LengthAwarePaginator (page 1, perPage = limit) so the old
     * paginate()-backed {data, links, meta} envelope NewAgencyRankingResource
     * produced is byte-identical.
     *
     * @param array<int, array{member:string, score:float}> $topN
     */
    public function hydrateAgencyRanking(array $topN): \Illuminate\Pagination\LengthAwarePaginator
    {
        $perPage = max(1, count($topN));

        if (empty($topN)) {
            return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, 10, 1, [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
            ]);
        }

        $ids = array_map(fn ($r) => (int) $r['member'], $topN);
        $cacheKey = 'rank:hydrate:agency:' . md5(implode(',', $ids));

        $agencies = \Cache::remember($cacheKey, 20, function () use ($ids) {
            return Agency::query()
                ->whereIn('id', $ids)
                ->with('owner')
                ->select(['id', 'name', 'notice', 'phone', 'img', 'app_owner_id'])
                ->get()
                ->keyBy('id');
        });

        $result = collect();
        foreach ($topN as $row) {
            $agency = $agencies[(int) $row['member']] ?? null;
            if (!$agency) {
                continue;
            }

            $item = new GiftRanking();
            $item->total_gifts = (float) $row['score'];
            $item->setRelation('ranker', $agency);
            $result->push($item);
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $result->values(),
            $result->count(),
            $perPage,
            1,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Hydrate avatars/covers for the home top-ranking strip from a unified-ranking
     * topN (member = user id; for the room section that user id is the room owner).
     * One WHERE id IN with only the avatar/cover relation, preserving Redis order.
     * $isRoom → ownerRoom.room_cover, else profile.avatar.
     *
     * @param array<int, array{member:string, score:float}> $topN
     */
    public function topRankingAvatars(array $topN, bool $isRoom): \Illuminate\Support\Collection
    {
        if (empty($topN)) {
            return collect();
        }

        $ids = array_map(fn ($r) => (int) $r['member'], $topN);

        $users = User::query()
            ->whereIn('id', $ids)
            ->select('id')
            ->when($isRoom, fn ($q) => $q->with('ownerRoom:id,uid,room_cover'))
            ->when(!$isRoom, fn ($q) => $q->with('profile:user_id,avatar'))
            ->get()
            ->keyBy('id');

        $result = collect();
        foreach ($topN as $row) {
            $user = $users[(int) $row['member']] ?? null;
            if (!$user) {
                continue;
            }
            $value = $isRoom
                ? (optional($user->ownerRoom)->room_cover)
                : (optional($user->profile)->avatar);
            if ($value) {
                $result->push($value);
            }
        }

        return $result;
    }

    /**
     * Limited eager-load for the hydrated ranking cards. Matches the relations
     * RankingService::transformData reads (profile/vip/manager/levels/country/
     * medals, + ownerRoom for the room section) so it never lazy-loads per row.
     */
    private function hydrateUserRelations(int $class): array
    {
        $relations = [
            'profile:user_id,avatar,birthday',
            'mangerType:id,name_ar,name_en,img',
            'UserVip:id,user_id,expire,level,is_used',
            'UserVip.OVip:id,level,img',
            'country:id,name,iso,flag',
            'medals' => fn ($q) => $q->where('picked', true)
                ->with([
                    'achievementLevel' => fn ($q) => $q->select(['id', 'valid_image'])
                        ->with('achievement:id,name,type'),
                ])
                ->limit(5),
        ];

        if ($class == 3) {
            $relations[] = 'ownerRoom:id,uid,room_cover,room_name';
        }

        return $relations;
    }

    /**
     * Inclusive [start, end] window for a unified-ranking period in the configured
     * timezone, honouring week_start for the weekly bucket. Mirrors
     * RankingScoreService::bucketToken boundaries so backfill rebuilds the SAME
     * bucket the live ZINCRBYs write into.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rankingPeriodWindow(string $period): array
    {
        // Delegate to RankingScoreService::periodWindow — the SINGLE source of truth
        // bucketToken() also derives from — so the window this backfill SUMs is
        // provably the same bucket the live ZINCRBYs write into (weekly end derived
        // from the week_start anchor, not Carbon's misaligned argument-less endOfWeek).
        return app(\App\Services\RankingScoreService::class)->periodWindow($period);
    }

    /**
     * Backfill source for a gift-fed section (wealth|charm|room|agency) — the SAME
     * SUM(giftPrice) GROUP BY {column} the live per-row ZINCRBYs reproduce, scoped
     * to the period window. No LIMIT (the full bucket is rebuilt). Returns
     * [member => score].
     *
     * @return array<int|string, float>
     */
    public function backfillGiftScores(string $column, string $period): array
    {
        [$start, $end] = $this->rankingPeriodWindow($period);

        return $this->sumGiftScoresBetween($column, $start, $end);
    }

    /**
     * SUM(giftPrice) GROUP BY $column for the base build: rows in [start, watermark]
     * (inclusive both ends). Pairs with sumGiftScoresAfter for the gap tail so the
     * two windows are disjoint at the watermark — no overlap, no double-count.
     * Returns [member => score].
     *
     * @return array<int|string, float>
     */
    public function sumGiftScoresBetween(string $column, $start, $watermark, bool $excludeLucky = false): array
    {
        return $this->giftScoresQuery($column, $excludeLucky)
            ->where('created_at', '>=', $this->toStorageTime($start))
            ->where('created_at', '<=', $this->toStorageTime($watermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    /**
     * Normalise a period-window bound (a Carbon in the configured panel timezone,
     * from RankingScoreService::periodWindow) to the timezone gift_logs/
     * fair_luck_transactions actually store created_at in — config('app.timezone')
     * (UTC by default), since the rows are written with date('Y-m-d H:i:s', time()).
     *
     * Without this the panel-local wall clock (e.g. 13:00 Riyadh) is bound as a
     * string against a UTC-stored column (10:00), so the current hour falls outside
     * the window and the hourly fallback SUM returns nothing.
     */
    private function toStorageTime($bound): string
    {
        return Carbon::parse($bound)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * SUM(giftPrice) GROUP BY $column for an incremental delta window: rows in
     * (prevWatermark, newWatermark] — STRICTLY after the previous watermark
     * (disjoint from what the bucket already holds) and inclusive of the new one
     * (the next call's strict ">" picks up disjointly from here). This is the
     * range-bound tail the monthly incremental backfill ZINCRBYs each cycle so it
     * never re-scans the whole month. Returns [member => score].
     *
     * @return array<int|string, float>
     */
    public function sumGiftScoresInRange(string $column, $prevWatermark, $newWatermark, bool $excludeLucky = false): array
    {
        return $this->giftScoresQuery($column, $excludeLucky)
            ->where('created_at', '>', $this->toStorageTime($prevWatermark))
            ->where('created_at', '<=', $this->toStorageTime($newWatermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    /**
     * SUM(giftPrice) GROUP BY $column for the gap tail: rows STRICTLY after the
     * watermark (created_at > watermark), i.e. the live writes a preceding
     * replaceBucket RENAME dropped. No microsecond arithmetic — the strict ">"
     * guarantees disjointness from the inclusive base. Returns [member => score].
     *
     * @return array<int|string, float>
     */
    public function sumGiftScoresAfter(string $column, $watermark, bool $excludeLucky = false): array
    {
        return $this->giftScoresQuery($column, $excludeLucky)
            ->where('created_at', '>', $this->toStorageTime($watermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    /**
     * SUM(giftPrice) GROUP BY $column over gift_logs.
     *
     * $excludeLucky → drop rows whose gift is a lucky gift. The authoritative lucky
     * marker is the GIFT TYPE (gifts.type == 6) — the SAME check the send path uses
     * (GiftLogController::is_lucky_gift = $gift->type == 6). NOT the lucky_gifts table,
     * which is a stale/partial config catalog that does not cover every live lucky gift
     * id (e.g. test gifts 2483/2487-2489 are type=6 but absent from lucky_gifts), so a
     * lucky_gifts-based exclusion silently leaks the host-cut back into wealth. Owner
     * decision: wealth(sender) must NOT count the lucky giftPrice — that value is the
     * HOST CUT, not the sender's spend; the sender's lucky contribution to wealth is the
     * TURNOVER (fair_luck_transactions.bet_amount), merged in separately. Charm/room/
     * agency keep counting lucky giftPrice unchanged (host-cut received is correct for
     * them), so they pass $excludeLucky = false.
     */
    private function giftScoresQuery(string $column, bool $excludeLucky = false)
    {
        $query = DB::table('gift_logs')
            ->select($column . ' as member', DB::raw('SUM(giftPrice) as score'))
            ->whereNotNull($column)
            ->where($column, '!=', 0);

        if ($excludeLucky) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('gifts')
                    ->whereColumn('gifts.id', 'gift_logs.giftId')
                    ->where('gifts.type', 6);
            });
        }

        return $query->groupBy($column);
    }

    /**
     * Merge two [member => score] maps by summing per member. Used to combine the
     * normal-gift giftPrice base/tail with the lucky turnover base/tail for wealth,
     * so both the backfill and the on-read fallback reconstruct the IDENTICAL value
     * the live ZINCRBYs hold (normal giftPrice + lucky turnover per sender).
     *
     * @param array<int|string, float> $a
     * @param array<int|string, float> $b
     * @return array<int|string, float>
     */
    public function mergeScoreMaps(array $a, array $b): array
    {
        foreach ($b as $member => $score) {
            $a[$member] = ($a[$member] ?? 0.0) + (float) $score;
        }

        return $a;
    }

    /**
     * Backfill source for the lucky section. Owner decision: lucky measures
     * SPENDING (turnover) — SUM(bet_amount) from fair_luck_transactions, the SAME
     * value the runtime hook (total_diamond) feeds — NOT total_win. Scoped to the
     * period window. Returns [user_id => turnover].
     *
     * @return array<int, float>
     */
    public function backfillLuckyScores(string $period): array
    {
        [$start, $end] = $this->rankingPeriodWindow($period);

        return $this->sumLuckyScoresBetween($start, $end);
    }

    /**
     * SUM(bet_amount) GROUP BY user_id for the base build: rows in [start, watermark]
     * (inclusive). Pairs with sumLuckyScoresAfter for the disjoint gap tail.
     * Returns [user_id => turnover].
     *
     * @return array<int, float>
     */
    public function sumLuckyScoresBetween($start, $watermark): array
    {
        return $this->luckyScoresQuery()
            ->where('created_at', '>=', $this->toStorageTime($start))
            ->where('created_at', '<=', $this->toStorageTime($watermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    /**
     * SUM(bet_amount) GROUP BY user_id for an incremental delta window: rows in
     * (prevWatermark, newWatermark] — strictly after the previous watermark,
     * inclusive of the new one. The range-bound tail the monthly incremental
     * backfill ZINCRBYs each cycle so it never re-scans the whole month.
     * Returns [user_id => turnover].
     *
     * @return array<int, float>
     */
    public function sumLuckyScoresInRange($prevWatermark, $newWatermark): array
    {
        return $this->luckyScoresQuery()
            ->where('created_at', '>', $this->toStorageTime($prevWatermark))
            ->where('created_at', '<=', $this->toStorageTime($newWatermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    /**
     * SUM(bet_amount) GROUP BY user_id for the gap tail: rows STRICTLY after the
     * watermark (created_at > watermark). Returns [user_id => turnover].
     *
     * @return array<int, float>
     */
    public function sumLuckyScoresAfter($watermark): array
    {
        return $this->luckyScoresQuery()
            ->where('created_at', '>', $this->toStorageTime($watermark))
            ->pluck('score', 'member')
            ->map(fn ($s) => (float) $s)
            ->toArray();
    }

    private function luckyScoresQuery()
    {
        return DB::table('fair_luck_transactions')
            ->select('user_id as member', DB::raw('SUM(bet_amount) as score'))
            ->whereNotNull('user_id')
            ->where('user_id', '!=', 0)
            ->groupBy('user_id');
    }

    /** section → gift_logs column for the on-read fallback (mirrors the backfill map). */
    private const FALLBACK_GIFT_COLUMN = [
        'wealth' => 'sender_id',
        'charm'  => 'receiver_id',
        'room'   => 'roomowner_id',
        'agency' => 'agency_id',
    ];

    /**
     * On-read DB fallback for a section/period, returned in RankingScoreService::topN
     * shape ([['member'=>string,'score'=>float], ...], score-desc, sliced to $limit).
     *
     * Used ONLY when the live Redis bucket is empty — the hourly bucket rolls every
     * hour and stays empty until the first write or the 15-min backfill, so without
     * this the hourly board is blank for up to 15 min each hour. The same SUM source
     * the backfill uses, cached briefly so a thundering herd at the hour boundary
     * hits the DB once, then the next write/backfill repopulates Redis.
     *
     * @return array<int, array{member:string, score:float}>
     */
    public function fallbackTopN(string $section, string $period, int $limit): array
    {
        $cacheKey = "rank:fallback:{$section}:{$period}:" . $this->rankingPeriodWindow($period)[0]->format('YmdH');

        return \Cache::remember($cacheKey, 30, function () use ($section, $period, $limit) {
            if ($section === 'lucky') {
                $scores = $this->backfillLuckyScores($period);
            } elseif ($section === 'wealth') {
                // Wealth(sender) = normal-gift giftPrice (lucky excluded) + lucky
                // turnover (bet_amount). SAME composition as the live ZINCRBYs and
                // the 15-min backfill, so the on-read fallback matches both.
                [$start] = $this->rankingPeriodWindow($period);
                $normal = $this->sumGiftScoresBetween(self::FALLBACK_GIFT_COLUMN['wealth'], $start, $this->rankingPeriodWindow($period)[1], true);
                $scores = $this->mergeScoreMaps($normal, $this->backfillLuckyScores($period));
            } elseif (isset(self::FALLBACK_GIFT_COLUMN[$section])) {
                $scores = $this->backfillGiftScores(self::FALLBACK_GIFT_COLUMN[$section], $period);
            } else {
                // games/agency-by-other or unknown: no gift_logs source here.
                return [];
            }

            arsort($scores);

            $out = [];
            foreach (array_slice($scores, 0, $limit, true) as $member => $score) {
                if ((float) $score == 0.0 || (string) $member === '0' || $member === '' || $member === null) {
                    continue;
                }
                $out[] = ['member' => (string) $member, 'score' => (float) $score];
            }

            return $out;
        });
    }

    public function getUserGameCoins(int $type, int $limit)
    {
        [$from, $to] = $this->getDateRange($type);

        // Try Redis sorted set first (real-time, O(range))
        try {
            $rankingService = app(\App\Services\GameRankingService::class);
            if ($rankingService->hasData($type)) {
                $topPlayers = $rankingService->getTopPlayers($type, $limit);
                if (!empty($topPlayers)) {
                    return $this->hydrateUsersForRedisRanking($topPlayers);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Redis ranking failed, falling back to DB', ['error' => $e->getMessage()]);
        }

        // Fallback to DB (first load or Redis unavailable)
        // Cache result briefly so Redis gets populated on next game play
        $cacheKey = 'game_rank_db:' . $type . ':' . $from->toDateString();
        return \Cache::remember($cacheKey, 60, function () use ($type, $from, $to, $limit) {
            if ($type === 0 || $type === 1) {
                return $this->queryGameRankingFromTable('coin_game_users', $from, $to, $limit);
            }
            return $this->queryGameRankingUnion($from, $to, $limit);
        });
    }

    private function hydrateUsersForRedisRanking(array $topPlayers): \Illuminate\Support\Collection
    {
        $userIds = array_column($topPlayers, 'user_id');
        $expMap  = array_column($topPlayers, 'exp', 'user_id');

        // Query User model directly — NOT coin_game_users.
        // Monthly top players often exist only in coin_game_users_archive,
        // so querying the current table would drop them (the "monthly shows 1 user" bug).
        $users = \App\Models\User::query()
            ->whereIn('id', $userIds)
            ->with([
                'country:id,name,iso,flag',
                'profile:user_id,avatar,birthday',
                'mangerType:id,name_ar,name_en,img',
                'UserVip:id,user_id,expire,level,is_used',
            ])
            ->get()
            ->keyBy('id');

        // Build CoinGameUser-shaped objects preserving Redis order (already sorted by score)
        $result = collect();
        foreach ($topPlayers as $tp) {
            $user = $users[$tp['user_id']] ?? null;
            if (!$user) continue;

            $item = new CoinGameUser();
            $item->user_id = $tp['user_id'];
            $item->exp = $tp['exp'];
            $item->setRelation('user', $user);
            $result->push($item);
        }

        return $result;
    }

    private function queryGameRankingFromTable(string $table, $from, $to, int $limit)
    {
        $dateCol = $table === 'coin_game_users' ? 'created_at' : 'created_at';

        return CoinGameUser::query()
            ->from($table)
            ->select('user_id', DB::raw("SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as exp"))
            ->where('type', 1)
            ->whereBetween($dateCol, [$from, $to])
            ->whereHas('user')
            ->with($this->userRelations())
            ->groupBy('user_id')
            ->orderByDesc('exp')
            ->limit($limit)
            ->get();
    }

    private function queryGameRankingUnion($from, $to, int $limit)
    {
        // Full past days come from coin_game_users_daily_aggregated (total_win
        // is exactly SUM(coins) WHERE type=1 — verified bit-equal vs the raw
        // tables); only the days the aggregate doesn't cover yet are read from
        // the raw live + archive rows. Coverage is taken from MAX(date), NOT
        // assumed to be "yesterday": the coin-game:aggregate cron runs at
        // 07:00, so between midnight and the run the aggregate lags one day.
        // Raw-scanning the archive for a whole month took ~19s (3M+ rows in a
        // single month) and was the /api/ranking 504 source.
        $tz     = Common::timeZone();
        $maxAgg = DB::table('coin_game_users_daily_aggregated')->max('date');
        $aggTo  = $maxAgg ? Carbon::parse($maxAgg, $tz) : null;

        $agg = DB::table('coin_game_users_daily_aggregated')
            ->select('user_id', DB::raw("SUM(total_win) as exp"))
            ->whereBetween('date', [
                $from->toDateString(),
                $aggTo ? $aggTo->toDateString() : $from->copy()->subDay()->toDateString(),
            ])
            ->groupBy('user_id');

        $rawFrom = $aggTo ? $aggTo->copy()->addDay()->startOfDay() : $from->copy();
        if ($from->gt($rawFrom)) {
            $rawFrom = $from;
        }

        $live = DB::table('coin_game_users')
            ->select('user_id', DB::raw("SUM(coins) as exp"))
            ->where('type', 1)
            ->whereBetween('created_at', [$rawFrom, $to])
            ->groupBy('user_id');

        $archive = DB::table('coin_game_users_archive')
            ->select('user_id', DB::raw("SUM(coins) as exp"))
            ->where('type', 1)
            ->whereBetween('created_at', [$rawFrom, $to])
            ->groupBy('user_id');

        // Combine and re-aggregate
        $combined = DB::table(DB::raw("({$agg->toSql()} UNION ALL {$live->toSql()} UNION ALL {$archive->toSql()}) as combined"))
            ->mergeBindings($agg)
            ->mergeBindings($live)
            ->mergeBindings($archive)
            ->select('user_id', DB::raw("SUM(exp) as exp"))
            ->groupBy('user_id')
            ->orderByDesc('exp')
            ->limit($limit)
            ->pluck('exp', 'user_id');

        $userIds = $combined->keys()->toArray();
        if (empty($userIds)) return collect();

        // Hydrate from User model (archive users aren't in coin_game_users)
        $topPlayers = [];
        foreach ($combined as $userId => $exp) {
            $topPlayers[] = ['user_id' => (int) $userId, 'exp' => (int) $exp];
        }
        return $this->hydrateUsersForRedisRanking($topPlayers);
    }


    protected function getCoinsForModel(string $model, $from, $to, int $limit)
    {
        return $model::query()
            ->select('user_id', DB::raw("SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as exp"))
            ->whereBetween('date', [$from, $to])
            ->whereHas('user')
            ->with($this->userRelations())
            ->groupBy('user_id')
            ->orderByDesc('exp')
            ->limit($limit)
            ->get();
    }


    protected function userRelations(): array
    {
        return [
            'user:id,name,email,country_id',
            'user.country:id,name,iso,flag',
            'user.profile:user_id,avatar,birthday',
            'user.mangerType:id,name_ar,name_en,img',
            'user.UserVip:id,user_id,expire,level,is_used',
        ];
    }


    protected function getDateRange(int $type): array
    {
        $timezone = Common::timeZone();
        switch ($type) {
            case 0:
                $from = Carbon::now($timezone)->startOfHour();
                $to   = Carbon::now($timezone)->endOfHour();
                break;

            case 1:
                $from = Carbon::now($timezone)->startOfDay();
                $to   = Carbon::now($timezone)->endOfDay();
                break;

            case 2:
                $from = Carbon::now($timezone)->startOfWeek();
                $to   = Carbon::now($timezone)->endOfWeek();
                break;

            case 3:
                $from = Carbon::now($timezone)->startOfMonth();
                $to   = Carbon::now($timezone)->endOfMonth();
                break;

            default:
                $from = Carbon::now($timezone)->startOfDay();
                $to   = Carbon::now($timezone)->endOfDay();
                break;
        }

        return [$from, $to];
    }


    public function getGiftLogsForRoomOwnerId($class, $rel, $type, $limit, $room_id, $keywords)
    {
        $query = GiftLog::query()->where('roomowner_id', $room_id)->whereHas($rel)

            ->when($class != 3, fn($q) => $q->with($rel));

        $this->applyDateFilters($query, $type);

        return $query->selectRaw("sum(giftPrice) as exp, $keywords")
            ->groupBy($keywords)->orderByRaw("exp desc")
            ->limit($limit)->get()->reject(function ($q) {
                return $q->exp == 0;
            });
    }

    public function getGiftLogsUserForRoomOwnerId($class, $rel, $type, $userId, $room_id, $keywords)
    {
        $query = GiftLog::query()->where('roomowner_id', $room_id)->whereHas($rel)

            ->when($class != 3, fn($q) => $q->with($rel));

        $this->applyDateFilters($query, $type);

        return $query->selectRaw("sum(giftPrice) as exp, $keywords")->where($keywords, $userId)->groupBy($keywords)->first();
    }

    public function getGiftLogsForRoomById($class, $rel, $type, $limit, $room_id, $keywords)
    {
        $query = GiftLog::query()->where('room_id', $room_id)->whereHas($rel)

            ->when($class != 3, fn($q) => $q->with($rel));

        $this->applyDateFilters($query, $type);

        return $query->selectRaw("sum(giftPrice) as exp, $keywords")
            ->groupBy($keywords)->orderByRaw("exp desc")
            ->limit($limit)->get()->reject(function ($q) {
                return $q->exp == 0;
            });
    }

    public function getGiftLogsUserForRoomById($class, $rel, $type, $userId, $room_id, $keywords)
    {
        $query = GiftLog::query()->where('room_id', $room_id)->whereHas($rel)

            ->when($class != 3, fn($q) => $q->with($rel));

        $this->applyDateFilters($query, $type);

        return $query->selectRaw("sum(giftPrice) as exp, $keywords")->where($keywords, $userId)->groupBy($keywords)->first();
    }

    protected function applyDateFilters(&$query, $type)
    {
        $timezone = Common::timeZone();
        $now = Carbon::now($timezone);



        [$start, $end] = match ($type) {
            0 => [$now->copy()->startOfHour(), $now->copy()->endOfHour()],
            1 => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            2 => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            3 => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [null, null],
        };

        if ($start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }
    }
}
