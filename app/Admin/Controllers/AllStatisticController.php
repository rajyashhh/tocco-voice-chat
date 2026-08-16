<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Charge;
use App\Models\CoinLog;
use Encore\Admin\Widgets\Box;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\AgencySallary;
use Encore\Admin\Layout\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\Bd;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Agency;
use App\Models\GiftLog;
use App\Models\LiveTime;
use App\Models\UserTarget;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;

use Modules\Chat\Entities\ChatMessage;
use App\Models\CoinGameUserDailyAggregated;
use Carbon\CarbonPeriod;
use Modules\UsersWallet\Entities\WalletLog;

class AllStatisticController extends MainController
{
    public $permission_name = 'dashboard';

    protected function countryId()
    {
        return Common::filterCountryIds();
    }

    public static function countryIds(): array
    {
        return Common::filterCountryIds();
    }

    public function index(Content $content)
    {
        return parent::index(
            $content
                ->title(__('Home'))
                ->description(__('General Statistics'))
                ->row(function (Row $row) {
                    $row->column(12, view('admin.dashboard.chart'));
                })
        );
    }

    public function getTopFollowers(Request $request): JsonResponse
    {
        $countryID = $this->countryId();

        $topUsersByFollowers = User::withCount('followers')
            ->with('profile')
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->orderByDesc('followers_count')
            ->take(10)
            ->get()
            ->map(function ($user) {
                $avatar = $user->profile?->avatar;
                $user->avatar_url = $avatar ? getImagePath($avatar) : asset('images/businessman-icon.jpg');
                return $user;
            });

        return response()->json($topUsersByFollowers);
    }

    public function peakHours(Request $request)
    {
        $period = $request->get('period', 'day');
        $countryID = $this->countryId();
        $query = DB::table('live_times')
            ->join('users', 'live_times.uid', '=', 'users.id')
            ->when(
                $countryID,
                fn($q) =>
                $q->whereIn('users.country_id', (array) $countryID)
            );

        if ($period === 'day') {
            $query->selectRaw("FROM_UNIXTIME(live_times.start_time, '%H') as label, COUNT(*) as total")
                ->whereRaw("DATE(FROM_UNIXTIME(live_times.start_time)) = CURDATE()")
                ->groupBy('label');
        } elseif ($period === 'week') {
            $query->selectRaw("DATE(FROM_UNIXTIME(live_times.start_time)) as label, COUNT(*) as total")
                ->whereRaw("YEARWEEK(FROM_UNIXTIME(live_times.start_time)) = YEARWEEK(CURDATE())")
                ->groupBy('label');
        } elseif ($period === 'month') {
            $query->selectRaw("DATE(FROM_UNIXTIME(live_times.start_time)) as label, COUNT(*) as total")
                ->whereRaw("YEAR(FROM_UNIXTIME(live_times.start_time)) = YEAR(CURDATE())
                            AND MONTH(FROM_UNIXTIME(live_times.start_time)) = MONTH(CURDATE())")
                ->groupBy('label');
        }

        $rows = $query->orderBy('label')->get();

        return response()->json([
            'success' => true,
            'labels' => $rows->pluck('label'),
            'data' => $rows->pluck('total'),
        ]);
    }

    public function onlineStats()
    {
        $countryID = $this->countryId();
        $online = User::where('online', 1)->when(
            $countryID,
            fn($q) =>
            $q->whereIn('users.country_id', (array) $countryID)
        )->count();
        $offline = User::where('online', 0)->when(
            $countryID,
            fn($q) =>
            $q->whereIn('users.country_id', (array) $countryID)
        )->count();


        return response()->json(data: [
            'online' => $online,
            'offline' => $offline,
        ]);
    }

    public function roomsActivity(Request $request)
    {
        $period = $request->get('period', 'day');

        if ($period === 'day') {
            $dates = collect(range(0, 6))
                ->map(fn($i) => now()->subDays($i)->format('Y-m-d'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereDate('created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $newRooms = $dates->map(fn($d) => $newRoomsData[$d] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->whereDate('live_times.created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(live_times.created_at) as date, COUNT(DISTINCT users.id) as active_owners')
                ->groupBy('date')
                ->pluck('active_owners', 'date');

            $totalRooms = Room::count();

            $inactiveRooms = $dates->map(fn($d) => $totalRooms - ($activeOwners[$d] ?? 0));

            $labels = $dates;
        } elseif ($period === 'week') {
            $weeks = collect(range(0, 3))
                ->map(fn($i) => now()->subWeeks($i)->format('o-\WW'))
                ->reverse()
                ->values();

            $newRoomsData = Room::where('created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(created_at) as year, WEEK(created_at,1) as week, COUNT(*) as total")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->total]);

            $newRooms = $weeks->map(fn($w) => $newRoomsData[$w] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->whereDate('live_times.created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(live_times.created_at) as year, WEEK(live_times.created_at,1) as week, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->active_owners]);

            $totalRooms = Room::count();

            $inactiveRooms = $weeks->map(fn($w) => $totalRooms - ($activeOwners[$w] ?? 0));

            $labels = $weeks;
        } elseif ($period === 'month') {
            // last 6 months
            $months = collect(range(0, 5))
                ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
                ->reverse()
                ->values();

            $newRoomsData = Room::where('created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym');

            $newRooms = $months->map(fn($m) => $newRoomsData[$m] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->where('live_times.created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(live_times.created_at,'%Y-%m') as ym, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('ym')
                ->pluck('active_owners', 'ym');

            $totalRooms = Room::count();

            $inactiveRooms = $months->map(fn($m) => $totalRooms - ($activeOwners[$m] ?? 0));

            $labels = $months;
        }

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'newRooms' => $newRooms,
            'inactiveRooms' => $inactiveRooms,
        ]);
    }

    public function topUsersData(Request $request)
    {
        $countryID = $this->countryId();

        $topUsers = LiveTime::query()
            ->whereHas('user', function ($q) use ($countryID) {
                $q->when($countryID, fn($q) => $q->whereIn('country_id', $countryID));
            })
            ->selectRaw('uid, SUM(hours) as total_hours')
            ->groupBy('uid')
            ->havingRaw('SUM(hours) >= 1')
            ->orderByDesc('total_hours')
            ->take(10)
            ->get();

        $labels = User::whereIn('id', $topUsers->pluck('uid'))
            ->pluck('name');

        $data = $topUsers->pluck('total_hours');

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    public function topUsersVisits(Request $request)
    {
        $countryID = $this->countryId();
        // Performance fix: replaced correlated subquery (withCount) with JOIN
        // Old query caused full table scan on live_times (74K rows) per user → 1+ hour stuck queries
        $topUsers = User::select('users.id', 'users.name', DB::raw('SUM(live_times.hours) as total_hours'))
            ->join('live_times', 'users.id', '=', 'live_times.uid')
            ->when($countryID, fn($q) => $q->whereIn('users.country_id', (array) $countryID))
            ->where('live_times.start_time', '>=', now()->subMonth()->timestamp)
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.name')
            ->having('total_hours', '>', 0)
            ->orderByDesc('total_hours')
            ->take(10)
            ->get();
        return response()->json([
            'labels' => $topUsers->pluck('name'),
            'data' => $topUsers->pluck('total_hours')
        ]);
    }


    protected function comparisonUserSignUp()
    {
        $currMonth = now()->month;
        $prevMonth = now()->subMonth()->month;
        $countryID = $this->countryId();

        $signups = User::when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        })
            ->selectRaw("
                                YEAR(created_at) as year,
                                MONTH(created_at) as month,
                                FLOOR((DAY(created_at)-1)/7)+1 as week_of_month,
                                COUNT(*) as total
                            ")
            ->whereIn(DB::raw('MONTH(created_at)'), [$currMonth, $prevMonth])
            ->groupBy('year', 'month', 'week_of_month')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('week_of_month')
            ->get();

        $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];

        $dataCurrent = [];
        $dataPrevious = [];

        foreach (range(1, 4) as $week) {
            $dataCurrent[] = $signups->where('month', $currMonth)->where('week_of_month', $week)->sum('total');
            $dataPrevious[] = $signups->where('month', $prevMonth)->where('week_of_month', $week)->sum('total');
        }

        $currMonthName = Carbon::create()->month($currMonth)->translatedFormat('F');
        $prevMonthName = Carbon::create()->month($prevMonth)->translatedFormat('F');

        return response()->json([
            'labels' => $labels,
            'dataCurrent' => $dataCurrent,
            'dataPrevious' => $dataPrevious,
            'currentMonth' => $currMonthName,
            'previousMonth' => $prevMonthName,
        ]);
    }

    protected function distributionRooms()
    {
        $countryID = $this->countryId();
        $roomsWithPk = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->when($countryID, function ($query, $countryID) {
                return $query->whereIn('country_id', $countryID);
            });
        })
            ->has('lastPk')
            ->count();

        $typeCounts = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->when($countryID, function ($query, $countryID) {
                return $query->whereIn('country_id', $countryID);
            });
        })
            ->whereIn('type', ['audio', 'live'])
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $audioRooms = $typeCounts['audio'] ?? 0;
        $liveRooms = $typeCounts['live'] ?? 0;

        $inactiveRooms = Room::whereHas('owner', fn($q) => $q->when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        }))
            ->whereDoesntHave('roomVisitors')
            ->count();

        $roomStats = [
            __('Rooms with PK') => $roomsWithPk,
            __('Audio Rooms') => $audioRooms,
            __('Live Rooms') => $liveRooms,
            __('Inactive Rooms') => $inactiveRooms,
        ];

        return response()->json([
            'labels' => array_keys($roomStats),
            'data' => array_values($roomStats),
        ]);
    }

    protected function topRoomGifts()
    {
        $countryID = $this->countryId();
        $topGiftedRooms = Room::whereHas('owner', fn($q) => $q->when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        }))
            ->with('owner')
            ->withSum('gifts', 'giftPrice')
            ->orderByDesc('gifts_sum_gift_price')
            ->take(10)
            ->get()
            ->filter(fn($room) => $room->gifts_sum_gift_price > 0);

        $labels = $topGiftedRooms->map(fn($room) => $room->owner->name ?? 'Unknown');
        $data = $topGiftedRooms->pluck('gifts_sum_gift_price');
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    protected function averageActiveRooms()
    {
        $avgSessionRooms = \DB::table('rooms')
            ->join('users', 'rooms.uid', '=', 'users.id')
            ->join('live_times', 'users.id', '=', 'live_times.uid')
            ->where('users.country_id', Auth::user()->country_id)
            ->select(
                'rooms.id',
                'users.name as room_name',
                \DB::raw('AVG(
                                        COALESCE(live_times.hours,
                                            TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(live_times.start_time), FROM_UNIXTIME(live_times.end_time)) / 3600
                                        )
                                    ) as avg_duration')
            )
            ->groupBy('rooms.id', 'users.name')
            ->orderByDesc('avg_duration')
            ->limit(10)
            ->get();

        $labels = $avgSessionRooms->pluck('room_name');
        $data = $avgSessionRooms->pluck('avg_duration');
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    protected function agencyTarget()
    {
        $countryID = $this->countryId();
        $topAgenciesByTargets = UserTarget::whereHas('agency', fn($q) => $q->when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        }))
            ->where('agency_obtain', '>', 0)
            ->selectRaw('agency_id, COUNT(*) as total_achieved')
            ->groupBy('agency_id')
            ->orderByDesc('total_achieved')
            ->with('agency:id,name')
            ->take(10)
            ->get();

        $labels = $topAgenciesByTargets->map(fn($t) => $t->agency->name ?? 'Unknown');
        $data = $topAgenciesByTargets->pluck('total_achieved');
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    protected function topSender()
    {
        $countryID = $this->countryId();
        $topSenders = GiftLog::whereHas(
            'sender',
            fn($q) => $q->when($countryID, function ($query, $countryID) {
                return $query->whereIn('country_id', $countryID);
            })
                ->whereHas('agency', fn($a) => $a->when($countryID, function ($query, $countryID) {
                    return $query->whereIn('country_id', $countryID);
                }))
        )
            ->selectRaw('sender_id, SUM(giftPrice) as total_sent')
            ->groupBy('sender_id')
            ->with('sender:id,name')
            ->orderByDesc('total_sent')
            ->take(10)
            ->get()
            ->filter(fn($s) => $s->total_sent > 0);

        $labels = $topSenders->map(fn($s) => $s->sender->name ?? 'Unknown');
        $data = $topSenders->pluck('total_sent');
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    protected function topReceiver()
    {
        $countryID = $this->countryId();
        $topReceivers = GiftLog::whereHas(
            'receiver',
            fn($q) => $q->when($countryID, function ($query, $countryID) {
                return $query->whereIn('country_id', $countryID);
            })
                ->whereHas('agency', fn($a) => $a->when($countryID, function ($query, $countryID) {
                    return $query->whereIn('country_id', $countryID);
                }))
        )
            ->selectRaw('receiver_id, SUM(giftPrice) as total_received')
            ->groupBy('receiver_id')
            ->with('receiver:id,name')
            ->orderByDesc('total_received')
            ->take(10)
            ->get()
            ->filter(fn($s) => $s->total_received > 0);

        $labels = $topReceivers->map(fn($r) => $r->receiver->name ?? 'Unknown');
        $data = $topReceivers->pluck('total_received');
        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    protected function comparisonAgencyTarget()
    {
        $countryID = $this->countryId();
        $achievedAgencies = Agency::when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        })
            ->whereHas('userTarget', function ($q) {
                $q->where('agency_obtain', '>', 0);
            })
            ->count();

        $notAchievedAgencies = Agency::when($countryID, function ($query, $countryID) {
            return $query->whereIn('country_id', $countryID);
        })
            ->count() - $achievedAgencies;

        return response()->json([
            'achieved' => $achievedAgencies,
            'notAchieved' => $notAchievedAgencies,
        ]);
    }

    public function roomStats()
    {
        $countryID = $this->countryId();
        $roomCounts = Room::whereHas('owner.country', fn($q) => $q->when($countryID, fn($query) => $query->whereIn('id', $countryID)))
            ->whereHas('roomVisitors')
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->pluck('total', 'type');


        $liveRooms = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->when($countryID, function ($query, $countryID) {
                return $query->whereIn('country_id', $countryID);
            });
        })
            ->where('type', 'live')
            ->selectRaw('is_live, COUNT(*) as total')
            ->groupBy('is_live')
            ->pluck('total', 'is_live');

        $liveRoomsTrue = $liveRooms[1] ?? 0;
        $liveRoomsFalse = $liveRooms[0] ?? 0;
        return response()->json([
            'audio' => $roomCounts['audio'] ?? 0,
            'live' => $roomCounts['live'] ?? 0,
            'active' => $liveRoomsTrue,
            'inactive' => $liveRoomsFalse,
        ]);
    }

    public function getStats()
    {
        $countryID = $this->countryId();

        $agencyCount = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))->count();

        $user_salaries = UserSallary::whereHas('user', function ($q) use ($countryID) {
            $q->where('agency_id', '!=', 0)
                ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
                ->whereHas('agency', fn($a) => $a->when($countryID, fn($q) => $q->whereIn('country_id', $countryID)));
        })->sum(DB::raw('sallary - cut_amount'));

        $agency_salaries = AgencySallary::whereHas(
            'agency',
            fn($q) => $q->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
        )->sum(DB::raw('sallary - cut_amount'));

        $activeAgencies = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->whereHas('agencySalaries', fn($q) => $q->where('month', now()->month)->where('year', now()->year))
            ->count();

        $newAgenciesToday = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->whereDate('created_at', today())
            ->count();

        $newAgenciesMonth = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $avgAgencyWallet = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))->avg('coins');

        $totalMembers = User::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->where('agency_id', '!=', 0)
            ->whereHas('agency', fn($q) => $q->when($countryID, fn($q) => $q->whereIn('country_id', $countryID)))
            ->count();

        $avgMembersPerAgency = $agencyCount > 0 ? round($totalMembers / $agencyCount) : 0;

        $pendingJoins = Agency::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->whereHas('joinRequests', fn($q) => $q->where('status', 1))
            ->count();

        $diamondsAchieved = UserSallary::whereHas('user', fn($q) => $q->when($countryID, fn($q) => $q->whereIn('country_id', $countryID)))
            ->whereHas('agency', fn($q) => $q->when($countryID, fn($q) => $q->whereIn('country_id', $countryID)))
            ->sum('achieved_diamond');

        return response()->json([
            'agencyCount' => $agencyCount,
            'user_salaries' => round($user_salaries),
            'agency_salaries' => round($agency_salaries),
            'activeAgencies' => $activeAgencies,
            'newAgenciesToday' => $newAgenciesToday,
            'newAgenciesMonth' => $newAgenciesMonth,
            'avgAgencyWallet' => round($avgAgencyWallet),
            'totalMembers' => $totalMembers,
            'avgMembersPerAgency' => $avgMembersPerAgency,
            'pendingJoins' => $pendingJoins,
            'diamondsAchieved' => $diamondsAchieved,
        ]);
    }


    public function getBdStats()
    {
        $countryID = $this->countryId();

        $bdCount = Bd::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))->count();

        $totalSalaries = Bd::when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->withSum('salaries', 'salary')
            ->withSum('salaries', 'cut_amount')
            ->withCount('agencies')
            ->get();

        $totalBDSalary = $totalSalaries->sum('salaries_sum_salary');
        $totalBDCut = $totalSalaries->sum('salaries_sum_cut_amount');
        $averageAgenciesPerBD = $totalSalaries->avg('agencies_count');

        return response()->json([
            'bdCount' => $bdCount,
            'totalBDSalary' => round($totalBDSalary),
            'totalBDCut' => round($totalBDCut),
            'averageAgenciesPerBD' => round($averageAgenciesPerBD, 2),
        ]);
    }

    /**
     * Resolve the country scope for the game endpoints: the session/area scope
     * (countryId) optionally narrowed by an explicit ?country_id= override, kept
     * identical to the original gameSummary contract so callers passing a country
     * keep working. An empty array means "all countries" (no filter).
     */
    private function gameCountryScope(Request $request): array
    {
        $requested = (int) $request->get('country_id');
        $scope = $this->countryId();
        if ($requested) {
            return (empty($scope) || in_array($requested, $scope, true)) ? [$requested] : [0];
        }
        return $scope;
    }

    public function gameSummary(Request $request): JsonResponse
    {
        $countryID = $this->gameCountryScope($request);
        $cacheKey = 'game_tab:summary:' . md5(json_encode($countryID));

        $data = Cache::remember($cacheKey, 120, function () use ($countryID) {
            // All-time totals — indexed daily aggregate (per user/game/day).
            $total = CoinGameUserDailyAggregated::query()
                ->when($countryID, function ($q) use ($countryID) {
                    $q->whereHas('user', fn($query) => $query->whereIn('country_id', $countryID));
                })
                ->selectRaw("
                    SUM(total_played) as total_played,
                    SUM(total_loss)   as total_loss,
                    SUM(total_win)    as total_win,
                    SUM(total_loss - total_win) as app_profit
                ")
                ->first();

            // Today — raw live table only (archived daily at 07:00). total_played
            // is SUM(coins) over both types, matching the aggregate's convention.
            $today = DB::table('coin_game_users as cgu')
                ->leftJoin('users as u', 'u.id', '=', 'cgu.user_id')
                ->whereDate('cgu.created_at', today())
                ->when($countryID, fn($q) => $q->whereIn('u.country_id', $countryID))
                ->selectRaw("
                    COALESCE(SUM(cgu.coins), 0) as total_played,
                    COALESCE(SUM(CASE WHEN cgu.type = 0 THEN cgu.coins ELSE 0 END), 0) as total_loss,
                    COALESCE(SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END), 0) as total_win,
                    COALESCE(SUM(CASE WHEN cgu.type = 0 THEN cgu.coins ELSE 0 END)
                           - SUM(CASE WHEN cgu.type = 1 THEN cgu.coins ELSE 0 END), 0) as app_profit,
                    COUNT(DISTINCT cgu.user_id) as active_players
                ")
                ->first();

            return [
                // Kept at top level for backward compatibility (#appProfit).
                'app_profit'   => (int) ($total->app_profit ?? 0),
                'total'        => [
                    'total_played' => (int) ($total->total_played ?? 0),
                    'total_loss'   => (int) ($total->total_loss ?? 0),
                    'total_win'    => (int) ($total->total_win ?? 0),
                    'app_profit'   => (int) ($total->app_profit ?? 0),
                ],
                'today'        => [
                    'total_played'   => (int) ($today->total_played ?? 0),
                    'total_loss'     => (int) ($today->total_loss ?? 0),
                    'total_win'      => (int) ($today->total_win ?? 0),
                    'app_profit'     => (int) ($today->app_profit ?? 0),
                    'active_players' => (int) ($today->active_players ?? 0),
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * Most-consumed games (by coins played) — today from the raw live table and
     * all-time from the indexed daily aggregate.
     *
     * game_id may hold all_games.id OR all_games.custom_id, so both are joined
     * and the name/image are COALESCE'd — the canonical resolution documented in
     * CoinGameUserService::buildGrid_details().
     */
    public function gameTopGames(Request $request): JsonResponse
    {
        $countryID = $this->gameCountryScope($request);
        $cacheKey = 'game_tab:top_games:' . md5(json_encode($countryID));

        $data = Cache::remember($cacheKey, 300, function () use ($countryID) {
            $today = DB::table('coin_game_users as cgu')
                ->leftJoin('users as u', 'u.id', '=', 'cgu.user_id')
                ->leftJoin('all_games as g', 'g.id', '=', 'cgu.game_id')
                ->leftJoin('all_games as cg', 'cg.custom_id', '=', 'cgu.game_id')
                ->whereDate('cgu.created_at', today())
                ->whereNotNull('cgu.game_id')
                ->when($countryID, fn($q) => $q->whereIn('u.country_id', $countryID))
                ->selectRaw("
                    cgu.game_id,
                    COALESCE(g.name, cg.name)   as game_name,
                    COALESCE(g.image, cg.image) as game_image,
                    SUM(cgu.coins) as total_played
                ")
                ->groupBy('cgu.game_id', 'g.name', 'cg.name', 'g.image', 'cg.image')
                ->orderByDesc('total_played')
                ->limit(10)
                ->get();

            $total = DB::table('coin_game_users_daily_aggregated as d')
                ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
                ->leftJoin('all_games as g', 'g.id', '=', 'd.game_id')
                ->leftJoin('all_games as cg', 'cg.custom_id', '=', 'd.game_id')
                ->when($countryID, fn($q) => $q->whereIn('u.country_id', $countryID))
                ->selectRaw("
                    d.game_id,
                    COALESCE(g.name, cg.name)   as game_name,
                    COALESCE(g.image, cg.image) as game_image,
                    SUM(d.total_played) as total_played
                ")
                ->groupBy('d.game_id', 'g.name', 'cg.name', 'g.image', 'cg.image')
                ->orderByDesc('total_played')
                ->limit(10)
                ->get();

            $map = function ($row) {
                return [
                    'game_id'      => $row->game_id,
                    'game_name'    => $row->game_name ?: ('#' . $row->game_id),
                    'game_image'   => getImagePath($row->game_image) ?: asset('images/businessman-icon.jpg'),
                    'total_played' => (int) $row->total_played,
                ];
            };

            return [
                'today' => $today->map($map)->values(),
                'total' => $total->map($map)->values(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Top users by coins played today — raw live table only (archived at 07:00).
     */
    public function gameTopUsers(Request $request): JsonResponse
    {
        $countryID = $this->gameCountryScope($request);
        $cacheKey = 'game_tab:top_users:' . md5(json_encode($countryID));

        $data = Cache::remember($cacheKey, 300, function () use ($countryID) {
            return DB::table('coin_game_users as cgu')
                ->leftJoin('users as u', 'u.id', '=', 'cgu.user_id')
                ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
                ->whereDate('cgu.created_at', today())
                ->whereNotNull('cgu.user_id')
                ->where('cgu.user_id', '!=', 0)
                ->when($countryID, fn($q) => $q->whereIn('u.country_id', $countryID))
                ->selectRaw("
                    cgu.user_id,
                    u.name,
                    u.uuid,
                    p.avatar,
                    SUM(cgu.coins) as total_played
                ")
                ->groupBy('cgu.user_id', 'u.name', 'u.uuid', 'p.avatar')
                ->orderByDesc('total_played')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    return [
                        'user_id'      => $row->user_id,
                        'name'         => $row->name ?: ('#' . $row->user_id),
                        'avatar_url'   => $row->avatar ? getImagePath($row->avatar) : asset('images/businessman-icon.jpg'),
                        'total_played' => (int) $row->total_played,
                    ];
                })
                ->values();
        });

        return response()->json($data);
    }


    public function getStatsData(Request $request)
    {
        try {
            $countryID = $this->countryIds();

            // تجميع جميع الاستعلامات في مرة واحدة
            $userBaseQuery = User::when($countryID, fn($q) => $q->whereIn('country_id', $countryID));

            $stats = [
                'usersCount' => (clone $userBaseQuery)->count(),
                'newSignUpsToday' => (clone $userBaseQuery)->whereDate('created_at', today())->count(),
                'newSignUpsThisWeek' => (clone $userBaseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'newSignUpsThisMonth' => (clone $userBaseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'onlineUser' => (clone $userBaseQuery)->where('online', 1)->count(),
            ];

            // Peak Hours
            $peakHours = LiveTime::whereHas('user', function ($q) use ($countryID) {
                $q->when($countryID, fn($query) => $query->whereIn('country_id', $countryID));
            })
                ->selectRaw("FROM_UNIXTIME(start_time, '%H') as hour, COUNT(*) as total_sessions")
                ->whereRaw("DATE(FROM_UNIXTIME(start_time)) = CURDATE()")
                ->groupBy('hour')
                ->orderByDesc('total_sessions')
                ->first();

            if ($peakHours) {
                $time = Carbon::createFromTime($peakHours->hour);
                $time->locale(app()->getLocale());
                $stats['peakHour'] = $time->isoFormat('h A') . ' • ' . $peakHours->total_sessions . ' ' . __('Users');
            } else {
                $stats['peakHour'] = '0';
            }

            // Chat Statistics
            $chatMessageQuery = ChatMessage::when($countryID, function ($q) use ($countryID) {
                $q->whereHas('user', fn($query) => $query->whereIn('country_id', $countryID));
            });

            $stats['messagesToday'] = (clone $chatMessageQuery)->whereDate('created_at', today())->count();
            $stats['messagesThisMonth'] = (clone $chatMessageQuery)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count();

            $stats['usersWhoSend'] = (clone $chatMessageQuery)->distinct('user_id')->count('user_id');
            $stats['usersWhoNeverSend'] = $stats['usersCount'] - $stats['usersWhoSend'];

            $stats['openConversationsToday'] = (clone $chatMessageQuery)->whereDate('created_at', today())
                ->distinct('chat_room_id')->count('chat_room_id');

            $perRoomDurations = ChatMessage::when($countryID, function ($q) use ($countryID) {
                $q->whereHas('user', fn($query) => $query->whereIn('country_id', $countryID));
            })
                ->selectRaw('TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration')
                ->groupBy('chat_room_id');

            $stats['avgConversationDuration'] = DB::query()
                ->fromSub($perRoomDurations, 'room_durations')
                ->avg('duration') ?? 0;

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics data'
            ], 500);
        }
    }

    public function financeCards(Request $request)
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $countryID = $this->countryIds();

        $result = UserSallary::when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->selectRaw('
                         SUM(pending_dollar) as total_dollars,
                         SUM(agency_sallary) as total_agency_dollars,
                         SUM(sallary) as total_user_dollars
                     ')
            ->first();

        $totalDollars = $result->total_dollars ?? 0;
        $totalAgencyDollars = $result->total_agency_dollars ?? 0;
        $totalUserDollars = $result->total_user_dollars ?? 0;

        $totalTargets = $totalDollars + $totalAgencyDollars + $totalUserDollars;

        $totalCharges = Charge::when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->sum('usd');

        $totalPayments = CoinLog::when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->sum('obtained_coins');

        $totalGiftsValue = GiftLog::when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->when($countryID, fn($q) => $q->whereHas('receiver', fn($q) => $q->whereIn('country_id', $countryID)))
            ->sum(\DB::raw('giftPrice'));

        $rate = Common::getCoinsValue('user_coins');
        $totalGiftsUsd = $rate > 0 ? $totalGiftsValue / $rate : 0;

        return response()->json([
            'total_balance' => $totalTargets,
            'pending_balance' => $totalCharges,
            'available_balance' => $totalPayments,
            'today_balance' => $totalGiftsUsd
        ]);
    }

    public function financeTables(Request $request)
    {
        $countryID = $this->countryIds();

        $payments = CoinLog::with('coin.paymentGateway')
            ->whereIn('status', [1, 2])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'gateway' => $p->coin->paymentGateway->title ?? '',
                'amount' => $p->obtained_coins,
                'status' => $p->status,
                'date' => \Carbon\Carbon::parse($p->created_at)->format('Y-m-d')
            ]);

        $withdrawals = WalletLog::with('user.profile')->where('operation', 'subtract')
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($w) {
                $defaultImage = asset('images/businessman-icon.jpg');
                $path = $w->user->profile?->avatar ?? null;
                $url = $path ? getImagePath($path) : $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                return [
                    'id' => $w->id,
                    'user_name' => $w->user->name ?? '',
                    'uuid' => $w->user->uuid ?? '',
                    'user_id' => $w->user_id,
                    'img' => $url,
                    'amount' => $w->amount,
                    'type' => $w->type,
                    'date' => $w->created_at->format('Y-m-d')
                ];
            });

        $topUsers = \DB::table('charges')
            ->select('user_id', \DB::raw('SUM(usd) as total_usd'), \DB::raw('MAX(created_at) as last_charge'))
            ->where('user_type', 'user')
            ->when($countryID, fn($q) => $q->whereIn('user_id', function ($sub) use ($countryID) {
                $sub->select('id')->from('users')->whereIn('country_id', $countryID);
            }))
            ->groupBy('user_id')
            ->orderByDesc('total_usd')
            ->limit(5)
            ->get();

        $userIds = $topUsers->pluck('user_id')->toArray();
        $usersMap = \App\Models\User::with('profile')->whereIn('id', $userIds)->get()->keyBy('id');

        $users = $topUsers->map(function ($u) use ($usersMap) {
            $user = $usersMap->get($u->user_id);

            $defaultImage = asset('images/businessman-icon.jpg');
            $path = $user?->profile?->avatar ?? '';

            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return [
                'id' => $u->user_id,
                'name' => $user->name ?? 'غير معروف',
                'uuid' => $user->uuid ?? 'غير معروف',
                'avatar' => $url,
                'total_usd' => $u->total_usd,
                'last_charge' => $u->last_charge,
            ];
        });
        return response()->json([
            'payments' => $payments,
            'withdrawals' => $withdrawals,
            'topUsers' => $users
        ]);
    }

    public function financeChartIndex(Request $request)
    {
        $days = (int)$request->query('days', 7);

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : $to->copy()->subDays($days - 1)->startOfDay();

        $period = CarbonPeriod::create($from, $to);

        $values = array_fill_keys(
            array_map(fn($d) => $d->format('Y-m-d'), iterator_to_array($period)),
            0
        );

        $countryID = $this->countryIds();

        $charges = Charge::selectRaw('DATE(created_at) as date, SUM(usd) as total')
            ->whereBetween('created_at', [$from, $to])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        foreach ($charges as $date => $total) {
            $values[$date] = (float)$total;
        }

        $labels = array_map(
            fn($d) => Carbon::parse($d)->format($days === 7 ? 'D' : 'd M'),
            array_keys($values)
        );

        return response()->json([
            'labels' => $labels,
            'values' => array_values($values),
        ]);
    }

    public function ajaxWalletLogs(Request $request)
    {

        $countryID = $this->countryIds();

        $logs = WalletLog::with('user.profile')
            ->whereIn('operation', ['add', 'cut'])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return response()->json([
            'data' => $logs->map(function ($log) {
                $defaultImage = asset('images/businessman-icon.jpg');
                $path = $log->user->profile?->avatar ?? null;
                $url = $path ? getImagePath($path) : $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                return [
                    'id' => $log->id,
                    'user_name' => $log->user ? $log->user->name : '-',
                    'user_id' => $log->user ? $log->user->id : '-',
                    'user_uuid' => $log->user ? $log->user->uuid : '-',
                    'img' => $url,
                    'amount' => $log->amount,
                    'operation' => $log->operation,
                    'type' => $log->type,
                    'before_amount' => $log->before_amount,
                    'after_amount' => $log->after_amount,
                    'created_at' => $log->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }


    public function index2(Content $content)
    {
        $box = new Box(
            __('Coming Soon'),
            '<div style="text-align:center; padding:30px; font-size:20px;">🚧</div>'
        );

        return $content
            ->title(__('Home'))
            ->description(__('General Statistics'))
            ->row($box);
    }
}
