<?php

namespace App\Admin\Controllers;

use App\Admin\Reports\LuckySummaryMapper;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY reporting page over fair_luck_transactions.
 *
 * The raw table is the SINGLE source of truth for every figure on the page — the
 * summary cards, the top winners/losers, and the round log all read it, so they can
 * never diverge. A nightly retention purge (cleanup:fair-luck-transactions --days=30)
 * keeps it bounded to the last 30 days, which is exactly the report's max range, so a
 * full-range aggregate is always a bounded read, never an unbounded historical scan.
 *
 * Performance contract — the page returns fast for ANY range:
 *   - Summary:
 *       * unfiltered "today"        → O(1) read of the per-day Redis running
 *         counters the gift job increments (no MySQL, no GROUP BY);
 *       * unfiltered multi-day      → raw aggregate over the (≤30-day) table,
 *         FULL breakdown (receivers/app/players included), shared via a short
 *         cache so concurrent admins never each scan the range;
 *       * a user/room/multiplier filter → raw, but tight: the filter pins the
 *         query to an index, so it reads that entity's rows only.
 *   - Top winners/losers: the two heavy GROUP BY user_id queries stream in after
 *     paint via the lazy `topLists` endpoint, cached per window/range across viewers.
 *   - The round log is always an index range-read: ORDER BY created_at DESC,
 *     id DESC over idx_flt_created_at (or idx_flt_user_created when user-filtered)
 *     + simplePaginate (no COUNT(*) over the period).
 * No FairLuck engine/money code is touched here.
 */
class LuckyGiftsReportController extends AdminController
{
    private const PER_PAGE = 50;
    private const MAX_RANGE_DAYS = 30;
    private const TOP_LIMIT = 10;
    // Short-TTL cache for the lazily-loaded top winners/losers of the unfiltered
    // "today" view: bounds the heavy GROUP BY to at most once per window across all
    // viewers, and it is off the page-paint path (AJAX). Tighter than the old 120s.
    private const TODAY_TOP_CACHE_TTL = 30; // seconds
    // Cache window for the unfiltered MULTI-DAY summary + top lists: one admin pays
    // the bounded range aggregate, the rest share it. 2 min is fresh enough for an
    // admin report whose range mostly covers settled past days.
    private const MULTIDAY_CACHE_TTL = 120; // seconds
    private const LIVE_VAULT_CACHE_TTL = 5; // seconds — bound the DB fallback

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'lucky-gift-setting');
        }

        $request = request();

        $f = $this->resolveFilters($request);
        [$from, $to] = [$f['from'], $f['to']];
        $result = $f['result'];
        $roomId = $f['roomId'];
        $roomInput = $f['roomInput'];
        $roomNotFound = $f['roomNotFound'];
        $multiplier = $f['multiplier'];
        $userTerm = $f['userTerm'];
        $filterUser = $f['filterUser'];
        $userNotFound = $f['userNotFound'];
        $hasEntityFilter = $f['hasEntityFilter'];

        // ---- Period summary -----------------------------------------------------
        // Routing decides whether we touch the raw 12M-row table at all. The heavy
        // top winners/losers GROUP BY is NOT computed here — it streams in after
        // paint via the lazy `topLists` endpoint (see §3), so the initial render is
        // a couple of O(1) reads and never queues behind the API workers.
        if ($userNotFound || $roomNotFound) {
            // Empty result set — no query needed.
            $summary = LuckySummaryMapper::map(null);
        } elseif ($hasEntityFilter) {
            // Filter pins the scan to one user/room/multiplier via its index → tight raw read.
            $summary = $this->rawSummary($from, $to, $filterUser, $roomId, $result, $multiplier);
        } elseif ($this->isCurrentDayOnly($from, $to)) {
            // Unfiltered "today": O(1) read of the per-day Redis running counters
            // the gift job increments — no MySQL, no GROUP BY, sub-millisecond.
            $summary = $this->todaySummaryFromCounters($to->toDateString());
        } else {
            // Unfiltered range crossing earlier days → raw aggregate (full
            // breakdown), bounded by the 30-day retention purge and shared via a
            // short cache so concurrent admins never each scan the range.
            $summary = $this->multiDaySummary($from, $to, $result);
        }

        // ---- Round log — always a bounded index range-read ---------------------
        $roundsQuery = DB::table('fair_luck_transactions')
            ->whereBetween('created_at', [$from, $to]);
        if ($filterUser !== null) {
            $roundsQuery->where('user_id', $filterUser->id);
        }
        if ($userNotFound || $roomNotFound) {
            $roundsQuery->whereRaw('1 = 0');
        }
        if ($roomId !== null) {
            $roundsQuery->where('room_id', $roomId);
        }
        // multiplier is set only on winning rows → this also pins is_winner = 1.
        if ($multiplier !== null) {
            $roundsQuery->where('multiplier', $multiplier);
        }
        if ($result !== null) {
            $roundsQuery->where('is_winner', $result === 'win' ? 1 : 0);
        }

        // newest first: created_at DESC, id DESC rides idx_flt_created_at
        // (or idx_flt_user_created when user-filtered); id breaks ties within a
        // second deterministically. simplePaginate avoids a period COUNT(*).
        $rounds = $roundsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE)
            ->appends($request->query());

        $pageItems = collect($rounds->items());
        $usersById = User::query()
            ->whereIn('id', $pageItems->pluck('user_id')->unique()->values())
            ->get(['id', 'name', 'uuid'])
            ->keyBy('id');
        $giftsById = DB::table('gifts')
            ->whereIn('id', $pageItems->pluck('gift_id')->unique()->values())
            ->get(['id', 'name', 'e_name'])
            ->keyBy('id');
        $roomNamesById = DB::table('rooms')
            ->whereIn('id', $pageItems->pluck('room_id')->filter()->unique()->values())
            ->pluck('room_name', 'id');

        // Top winners/losers render empty on first paint and are filled by the AJAX
        // `topLists` call (which carries its own player names), so no eager lookup
        // is needed here.
        return $content
            ->title('تقارير هدايا الحظ')
            ->description($from->format('Y-m-d') . ' ← ' . $to->format('Y-m-d'))
            ->view('lucky_gift_reports', [
                'summary' => $summary,
                'rounds' => $rounds,
                'usersById' => $usersById,
                'giftsById' => $giftsById,
                'roomNamesById' => $roomNamesById,
                'from' => $from,
                'to' => $to,
                'userTerm' => $userTerm,
                'userNotFound' => $userNotFound,
                'filterUser' => $filterUser,
                'result' => $result,
                'roomId' => $roomId,
                'roomInput' => $roomInput,
                'roomNotFound' => $roomNotFound,
                'multiplier' => $multiplier,
                'multiplierOptions' => self::multiplierOptions(),
                'maxRangeDays' => self::MAX_RANGE_DAYS,
                // Live, GLOBAL vault balance (not period-filtered) — same
                // authoritative read the FairLuck monitor uses.
                'liveVault' => $this->liveVault(),
            ]);
    }

    /**
     * Current vault balance: Redis is the source of truth (V7 redis-authoritative),
     * the durable wallet row is the fallback. Read-only — never seeds.
     *
     * Wrapped in a 5s micro-cache so that even if the vault Redis ever returns null
     * under load, the MySQL fallback fires at most once per 5s across all admins —
     * the "live" card tolerates 5s staleness, and the page never competes with the
     * API workers for a balance read.
     */
    private function liveVault(): int
    {
        return (int) Cache::remember('lucky_report:livevault', self::LIVE_VAULT_CACHE_TTL, function () {
            try {
                $raw = \App\Models\FairLuckWallet::vaultRedis()->get(\App\Services\FairLuck\V7\PoolManager::KEY_VAULT);
                if ($raw !== null) {
                    return (int) $raw;
                }
            } catch (\Throwable $e) {
                // fall through to the durable snapshot
            }

            return (int) (\App\Models\FairLuckWallet::where('wallet_type', \App\Models\FairLuckWallet::TYPE_UNIFIED_VAULT)->value('balance') ?? 0);
        });
    }

    /**
     * Shared base query over the raw table for the requested slice. The `result`
     * (win/lose) filter is applied only when $withResult — the top lists need both
     * sides to compute a net, so they pass false.
     */
    private function rawBase(Carbon $from, Carbon $to, ?object $filterUser, ?int $roomId, ?string $result, bool $withResult, ?int $multiplier = null)
    {
        $q = DB::table('fair_luck_transactions')
            ->whereBetween('created_at', [$from, $to]);
        if ($filterUser !== null) {
            $q->where('user_id', $filterUser->id);
        }
        if ($roomId !== null) {
            $q->where('room_id', $roomId);
        }
        // multiplier is set only on winning rows → this also pins is_winner = 1.
        if ($multiplier !== null) {
            $q->where('multiplier', $multiplier);
        }
        if ($withResult && $result !== null) {
            $q->where('is_winner', $result === 'win' ? 1 : 0);
        }
        return $q;
    }

    /**
     * Period summary over a tight (filtered or single-day) raw slice — one
     * date/index-bounded aggregate pass, no GROUP BY.
     */
    private function rawSummary(Carbon $from, Carbon $to, ?object $filterUser, ?int $roomId, ?string $result, ?int $multiplier = null): array
    {
        return LuckySummaryMapper::map(
            $this->rawBase($from, $to, $filterUser, $roomId, $result, true, $multiplier)->selectRaw(
                'COUNT(*) AS rounds,'
                . ' COALESCE(SUM(bet_amount), 0) AS total_bets,'
                . ' COALESCE(SUM(profit_amount), 0) AS senders_net,'
                . ' COALESCE(SUM(CASE WHEN is_winner = 1 THEN profit_amount + bet_amount ELSE 0 END), 0) AS total_payouts,'
                . ' COALESCE(SUM(receiver_fee), 0) AS receivers_total,'
                . ' COALESCE(SUM(app_fee), 0) AS app_total,'
                . ' COUNT(DISTINCT user_id) AS players,'
                . ' COALESCE(SUM(is_winner = 1), 0) AS wins'
            )->first()
        );
    }

    /**
     * Top net winners/losers over a raw slice — the two heavy GROUP BY user_id
     * queries, now invoked only from the lazy `topLists` endpoint.
     * @return array{0: array, 1: array} [winners[], losers[]] as plain arrays
     */
    private function rawTopLists(Carbon $from, Carbon $to, ?object $filterUser, ?int $roomId, ?string $result, ?int $multiplier = null): array
    {
        // "net" needs both sides → ignore the win/lose filter for the top lists.
        $groupSelect = 'user_id,'
            . ' COUNT(*) AS rounds,'
            . ' COALESCE(SUM(bet_amount), 0) AS total_bets,'
            . ' COALESCE(SUM(profit_amount), 0) AS net';
        $topWinners = $this->rawBase($from, $to, $filterUser, $roomId, $result, false, $multiplier)->selectRaw($groupSelect)
            ->groupBy('user_id')->havingRaw('net > 0')->orderByDesc('net')->limit(self::TOP_LIMIT)->get();
        $topLosers = $this->rawBase($from, $to, $filterUser, $roomId, $result, false, $multiplier)->selectRaw($groupSelect)
            ->groupBy('user_id')->havingRaw('net < 0')->orderBy('net')->limit(self::TOP_LIMIT)->get();

        return [
            $topWinners->map(fn ($r) => (array) $r)->all(),
            $topLosers->map(fn ($r) => (array) $r)->all(),
        ];
    }

    /**
     * Unfiltered multi-day period summary over the raw (≤30-day) table — the FULL
     * breakdown (receivers/app/players included), shared via a short cache so the
     * bounded range aggregate runs at most once per window across all admins.
     */
    private function multiDaySummary(Carbon $from, Carbon $to, ?string $result): array
    {
        $key = 'lucky_report:sum:' . $from->toDateString() . ':' . $to->toDateString()
            . ':' . ($result ?? 'all');

        return Cache::remember(
            $key,
            self::MULTIDAY_CACHE_TTL,
            fn () => $this->rawSummary($from, $to, null, null, $result)
        );
    }

    /**
     * Unfiltered multi-day top net winners/losers over the raw (≤30-day) table,
     * cached per range/result window so concurrent admins share one GROUP BY pass.
     * @return array{0: array, 1: array} [winners[], losers[]] as plain arrays
     */
    private function multiDayTopLists(Carbon $from, Carbon $to, ?string $result): array
    {
        $key = 'lucky_report:top:range:' . $from->toDateString() . ':' . $to->toDateString()
            . ':' . ($result ?? 'all');

        return Cache::remember(
            $key,
            self::MULTIDAY_CACHE_TTL,
            fn () => $this->rawTopLists($from, $to, null, null, $result)
        );
    }

    /**
     * Resolve the filter set (period, result, room, user, multiplier) exactly as
     * index() does. Shared by index() and the lazy topLists() endpoint so both
     * agree on scope.
     *
     * @return array{from: Carbon, to: Carbon, result: ?string, roomId: ?int,
     *               roomInput: string, roomNotFound: bool, multiplier: ?int,
     *               userTerm: string, filterUser: ?object, userNotFound: bool,
     *               hasEntityFilter: bool}
     */
    private function resolveFilters(Request $request): array
    {
        [$from, $to] = $this->resolvePeriod($request);

        $result = $request->input('result');
        $result = in_array($result, ['win', 'lose'], true) ? $result : null;

        // Multiplier filter: only an actually-configured win tier is accepted
        // (anything else is treated as "all multipliers"). fair_luck_transactions
        // .multiplier is set ONLY on winning rows (NULL on losses), so filtering by
        // it naturally scopes to the winners of that tier.
        $multiplierInput = (string) $request->input('multiplier', '');
        $multiplier = null;
        if (ctype_digit($multiplierInput)) {
            $candidate = (int) $multiplierInput;
            if (in_array($candidate, self::multiplierOptions(), true)) {
                $multiplier = $candidate;
            }
        }

        // Room field accepts a rooms.id OR the owner's room uid. Numeric input is
        // first taken as rooms.id; if no such id exists, it is resolved against
        // rooms.uid (the owner identifier) → that room's id. Either way the big
        // table is always filtered by the resolved numeric room_id (its index).
        $roomInput = (string) $request->input('room_id', '');
        $roomId = null;
        $roomNotFound = false;
        if (ctype_digit($roomInput)) {
            $roomId = $this->resolveRoomId((int) $roomInput);
            // A number that matches neither a rooms.id nor a rooms.uid → empty set,
            // never a silent "all rooms" (same contract as an unknown user term).
            $roomNotFound = $roomId === null;
        }

        // Resolve the user filter once (uuid / special id / numeric id) to a
        // single users.id so the big table is always filtered by its index.
        $userTerm = trim((string) $request->input('user', ''));
        $filterUser = null;
        $userNotFound = false;
        if ($userTerm !== '') {
            $filterUser = User::query()
                ->where(function ($q) use ($userTerm) {
                    $q->where('uuid', $userTerm)
                        ->orWhere('special_id', $userTerm);
                    if (ctype_digit($userTerm)) {
                        $q->orWhere('id', (int) $userTerm);
                    }
                })
                ->first(['id', 'name', 'uuid']);
            $userNotFound = $filterUser === null;
        }

        return [
            'from' => $from,
            'to' => $to,
            'result' => $result,
            'roomId' => $roomId,
            'roomInput' => $roomInput,
            'roomNotFound' => $roomNotFound,
            'multiplier' => $multiplier,
            'userTerm' => $userTerm,
            'filterUser' => $filterUser,
            'userNotFound' => $userNotFound,
            // Any of these forces the tight raw path (and multiplier/room cannot be
            // served from the today-counter or pre-agg summaries, which carry no
            // per-multiplier / per-room breakdown).
            'hasEntityFilter' => $filterUser !== null || $userNotFound
                || $roomId !== null || $roomNotFound || $multiplier !== null,
        ];
    }

    /**
     * The configured lucky win multipliers offered in the filter dropdown — the
     * single source of truth is the engine's MultiplierTable so the report can
     * never offer a tier the engine does not award.
     *
     * @return int[]
     */
    public static function multiplierOptions(): array
    {
        return \App\Services\FairLuck\V7\MultiplierTable::DEFAULT_MULTIPLIERS;
    }

    /**
     * Resolve the room field (a number) to a rooms.id. Tries rooms.id first; if no
     * row with data carries that id, falls back to rooms.uid = number (the owner's
     * room identifier). Returns null when the number matches neither.
     */
    private function resolveRoomId(int $number): ?int
    {
        if (DB::table('rooms')->where('id', $number)->exists()) {
            return $number;
        }
        $byUid = DB::table('rooms')->where('uid', $number)->value('id');

        return $byUid !== null ? (int) $byUid : null;
    }

    /**
     * Lazy AJAX endpoint for the heavy top winners/losers tables, fetched after the
     * page paints so the initial render is never blocked by the two GROUP BY scans.
     *
     * Accuracy is identical to the eager page: the unfiltered "today" view is served
     * from a short-TTL Redis cache around the SAME GROUP BY (bounded to once per
     * window across all viewers); filtered / past ranges hit the existing raw /
     * pre-agg paths unchanged. Returns { winners: [...], losers: [...] } with each
     * row carrying its own resolved player name/uuid.
     */
    public function topLists(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'lucky-gift-setting');
        }

        $f = $this->resolveFilters($request);
        $from = $f['from'];
        $to = $f['to'];

        if ($f['userNotFound'] || $f['roomNotFound']) {
            $winners = [];
            $losers = [];
        } elseif ($f['hasEntityFilter']) {
            [$winners, $losers] = $this->rawTopLists($from, $to, $f['filterUser'], $f['roomId'], $f['result'], $f['multiplier']);
        } elseif ($this->isCurrentDayOnly($from, $to)) {
            // Unfiltered "today": cache the two GROUP BY queries for a short window
            // so concurrent admins share one pass and never each scan the day.
            $cacheKey = 'lucky_report:top:' . $to->toDateString() . ':' . ($f['result'] ?? 'all');
            [$winners, $losers] = Cache::remember(
                $cacheKey,
                self::TODAY_TOP_CACHE_TTL,
                fn () => $this->rawTopLists($from, $to, null, null, $f['result'])
            );
        } else {
            // Unfiltered multi-day → raw GROUP BY over the (≤30-day) table, shared
            // via a short cache so concurrent admins share one pass.
            [$winners, $losers] = $this->multiDayTopLists($from, $to, $f['result']);
        }

        return response()->json([
            'winners' => $this->decorateTopRows($winners),
            'losers' => $this->decorateTopRows($losers),
        ]);
    }

    /**
     * Attach player name/uuid to top rows via a single whereIn lookup (the same
     * resolution the eager page did with $topUsersById).
     */
    private function decorateTopRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        $ids = collect($rows)->pluck('user_id')->unique()->values();
        $usersById = User::query()->whereIn('id', $ids)->get(['id', 'name', 'uuid'])->keyBy('id');

        return collect($rows)->map(function ($r) use ($usersById) {
            $uid = (int) ($r['user_id'] ?? 0);
            $u = $usersById->get($uid);
            return [
                'user_id' => $uid,
                'name' => $u->name ?? ('#' . $uid),
                'uuid' => $u->uuid ?? '-',
                'rounds' => (int) ($r['rounds'] ?? 0),
                'total_bets' => (int) ($r['total_bets'] ?? 0),
                'net' => (int) ($r['net'] ?? 0),
            ];
        })->all();
    }

    /**
     * O(1) "today" summary from the per-day Redis running counters the gift job
     * increments. No MySQL, no GROUP BY — two Redis ops (HGETALL + SCARD). On a
     * cold/missing hash (Redis flush, fresh day, manual reset) it seeds itself once
     * from SQL under a short lock, then serves — amortising the one cold pass across
     * all viewers, the same cost profile as the old 120s cache but only on miss.
     */
    private function todaySummaryFromCounters(string $date): array
    {
        $redis = \App\Models\FairLuckWallet::vaultRedis();
        $hkey = "lucky:day:{$date}";

        $h = $redis->hgetall($hkey);
        if (empty($h) || !isset($h['seeded'])) {
            $h = $this->seedTodayCounters($date);
        }

        $players = (int) $redis->scard("lucky:day:{$date}:players");

        return LuckySummaryMapper::map((object) [
            'rounds'          => (int) ($h['rounds'] ?? 0),
            'total_bets'      => (int) ($h['total_bets'] ?? 0),
            'senders_net'     => (int) ($h['senders_net'] ?? 0),
            'total_payouts'   => (int) ($h['total_payouts'] ?? 0),
            // Receiver fee is stored in bps-coins (= receiver fee × 10000) so sub-coin
            // fees on cheap gifts (50-coin bet at 1% → 0.50) are never floored to 0.
            // Recover the EXACT decimal = receivers_total_bps / 10000. Legacy hashes
            // carry whole-coin 'receivers_total' only — fall back so a mid-deploy
            // read never shows 0. Symmetric to readAppTotal.
            'receivers_total' => $this->readReceiversTotal($h),
            // Owner fee is stored in bps-coins (= owner fee × 10000) so sub-coin
            // fees on cheap gifts (10-coin bet → 0.10) are never floored to 0.
            // Recover the EXACT decimal = app_total_bps / 10000. Legacy hashes
            // seeded before the bps switch carry whole-coin 'app_total' only —
            // fall back to it so a mid-deploy read never shows 0.
            'app_total'       => $this->readAppTotal($h),
            'players'         => $players,
            'wins'            => (int) ($h['wins'] ?? 0),
        ]);
    }

    /**
     * Recover the exact decimal owner fee from a day-counter hash.
     *
     * The counter stores app_total_bps (integer bps-coins = owner fee × 10000) so
     * HINCRBY never floors a sub-coin fee. The report value is app_total_bps / 10000
     * — equal, coin-for-coin, to SQL SUM(app_fee). Falls back to a legacy whole-coin
     * 'app_total' field for hashes seeded before the bps switch.
     */
    private function readAppTotal(array $h): float
    {
        if (isset($h['app_total_bps'])) {
            return (int) $h['app_total_bps'] / 10000;
        }
        return (float) ($h['app_total'] ?? 0);
    }

    /**
     * Recover the exact decimal RECEIVER fee from a day-counter hash.
     *
     * Stores receivers_total_bps (integer bps-coins = receiver fee × 10000) so
     * HINCRBY never floors a sub-coin fee. Report value = receivers_total_bps / 10000
     * — equal, coin-for-coin, to SQL SUM(receiver_fee). Falls back to a legacy
     * whole-coin 'receivers_total' field for hashes seeded before the bps switch.
     */
    private function readReceiversTotal(array $h): float
    {
        if (isset($h['receivers_total_bps'])) {
            return (int) $h['receivers_total_bps'] / 10000;
        }
        return (float) ($h['receivers_total'] ?? 0);
    }

    /**
     * One-time SQL backfill of the day's counter hash + players SET, guarded by a
     * short NX lock so only one viewer/worker runs the cold pass. Losers fall back
     * to the SQL row they compute (correct, just not the writer). Returns the hash
     * field map the caller serves.
     *
     * @return array<string, int|string>
     */
    private function seedTodayCounters(string $date): array
    {
        $redis = \App\Models\FairLuckWallet::vaultRedis();
        $hkey = "lucky:day:{$date}";
        $pkey = "lucky:day:{$date}:players";

        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $row = DB::table('fair_luck_transactions')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                'COALESCE(COUNT(*), 0) AS rounds,'
                . ' COALESCE(SUM(bet_amount), 0) AS total_bets,'
                . ' COALESCE(SUM(profit_amount), 0) AS senders_net,'
                . ' COALESCE(SUM(CASE WHEN is_winner = 1 THEN profit_amount + bet_amount ELSE 0 END), 0) AS total_payouts,'
                . ' COALESCE(SUM(receiver_fee), 0) AS receivers_total,'
                . ' COALESCE(SUM(app_fee), 0) AS app_total,'
                . ' COALESCE(SUM(is_winner = 1), 0) AS wins'
            )
            ->first();

        // app_fee is decimal(12,2); SUM(app_fee) carries sub-coin owner fees (0.10
        // per cheap 10-coin bet). Store it in bps-coins (× 10000, exact integer) so
        // the HINCRBY-fed live counter and this backfill share one fraction-safe
        // unit and both equal ownerRate × turnover. (int) here would re-floor it to
        // 0 — the very defect the fraction fix removed.
        $appTotalBps = (int) round(((float) ($row->app_total ?? 0)) * 10000);
        // receiver_fee is decimal(12,2); same fraction-safe bps-coin unit as app fee
        // so the live counter and this backfill agree and both == ownerRate/recvRate
        // × turnover. (int) here would re-floor the sub-coin receiver fee to 0.
        $receiversTotalBps = (int) round(((float) ($row->receivers_total ?? 0)) * 10000);

        $fields = [
            'rounds'              => (int) ($row->rounds ?? 0),
            'total_bets'          => (int) ($row->total_bets ?? 0),
            'senders_net'         => (int) ($row->senders_net ?? 0),
            'total_payouts'       => (int) ($row->total_payouts ?? 0),
            'receivers_total_bps' => $receiversTotalBps,
            'app_total_bps'       => $appTotalBps,
            'wins'                => (int) ($row->wins ?? 0),
        ];

        // Only the lock winner writes Redis; everyone serves the correct SQL row.
        // Laravel's PhpRedisConnection::set($k,$v,$resolution,$ttl,$flag) emits the
        // phpredis options ['NX','EX'=>10] from these positional args.
        $gotLock = false;
        try {
            $gotLock = (bool) $redis->set("{$hkey}:seedlock", 1, 'EX', 10, 'NX');
        } catch (\Throwable $e) {
            // If the SET/NX flavour is unsupported, fall through and just serve SQL.
        }

        if ($gotLock) {
            try {
                $write = $fields + ['seeded' => 1];
                $redis->hmset($hkey, $write);
                $redis->expire($hkey, 172800);

                // Rebuild the distinct-senders SET from SQL so SCARD is exact.
                //
                // CRITICAL: page inside the day window via the created_at index.
                // DISTINCT user_id / ORDER BY user_id (and chunk()'s LIMIT/OFFSET)
                // let the optimizer pick the user_id-LEADING index and full-scan ALL
                // users on jo's 11.7M rows — the day range can't prune off a user_id
                // lead, so this seed timed out (MySQL 3024) like the reconciler. Keyset
                // on (created_at, id) — the SAME idx_flt_created_at range read the page
                // summary uses — touches ONLY the day's rows and we dedupe user_id in
                // PHP. forceIndex pins the day-range plan. Result is identical to the
                // old DISTINCT user_id over the window: one SET member per distinct
                // sender in [from, to].
                $redis->del($pkey);
                $lastCreatedAt = null;
                $lastId = null;
                $seen = [];
                while (true) {
                    $rows = DB::table('fair_luck_transactions')
                        ->forceIndex('idx_flt_created_at')
                        ->whereBetween('created_at', [$from, $to])
                        ->when(
                            $lastCreatedAt !== null,
                            fn ($q) => $q->whereRaw(
                                '(created_at > ? OR (created_at = ? AND id > ?))',
                                [$lastCreatedAt, $lastCreatedAt, $lastId]
                            )
                        )
                        ->select('id', 'created_at', 'user_id')
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->limit(5000)
                        ->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    $ids = [];
                    foreach ($rows as $r) {
                        $uid = (int) $r->user_id;
                        if ($uid > 0 && !isset($seen[$uid])) {
                            $seen[$uid] = true;
                            $ids[] = $uid;
                        }
                    }
                    if (!empty($ids)) {
                        $redis->sadd($pkey, ...$ids);
                    }

                    $last = $rows->last();
                    $lastCreatedAt = $last->created_at;
                    $lastId = $last->id;

                    if ($rows->count() < 5000) {
                        break;
                    }
                }
                $redis->expire($pkey, 172800);
            } catch (\Throwable $e) {
                // Non-fatal: serve the SQL row; the reconciler/next read repairs it.
            }
        }

        return $fields;
    }

    /**
     * True when the requested range lies entirely within the current day.
     */
    private function isCurrentDayOnly(Carbon $from, Carbon $to): bool
    {
        $today = Carbon::today();
        return $from->gte($today) && $to->lte($today->copy()->endOfDay());
    }

    /**
     * Default period = today. The range is hard-capped at MAX_RANGE_DAYS so a
     * single request can never aggregate months of data.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod($request): array
    {
        $from = $this->parseDate($request->input('from')) ?? Carbon::today();
        $to = $this->parseDate($request->input('to')) ?? Carbon::today();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            $from = $to->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function parseDate($value): ?Carbon
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
