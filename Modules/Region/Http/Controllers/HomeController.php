<?php

namespace Modules\Region\Http\Controllers;

use App\Models\Bd;
use App\Models\CoinLog;
use App\Models\GameChargeHistory;
use App\Models\GameWallet;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\GiftLog;
use App\Models\LiveTime;
use App\Models\UserTarget;
use App\Models\UserSallary;
use Carbon\CarbonPeriod;
use Encore\Admin\Layout\Row;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\AgencySallary;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Enums\Charges\UserTypeEnum;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Entities\ChatMessage;
use App\Admin\Controllers\MainController;
use App\Models\CoinGameUserDailyAggregated;
use Modules\Country\Entities\SuperAdmin;
use Modules\UsersWallet\Entities\WalletLog;

class HomeController extends MainController
{
    public $permission_name = 'dashboard';

    public function countries(): array
    {
        return Common::areaCountries();
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

        $countries = Common::areaCountries();
        $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;
        $superAdmins = SuperAdmin::where('parent_id', $authId)->pluck('id')->toArray();
        $usersCount = User::whereIn('country_id', $countries)->count();

        //users
        $newSignUpsToday = User::whereDate('created_at', today())->whereIn('country_id', $countries)->count();
        $newSignUpsThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->whereIn('country_id', $countries)->count();
        $newSignUpsThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->whereIn('country_id', $countries)->count();
        $onlineUser = User::whereIn('country_id', $countries)->where('online', 1)->count();
        $topUsersByFollowers = User::withCount('followers')
            ->with('packs', 'profile')
            ->whereIn('country_id', $countries)
            ->orderByDesc('followers_count')
            ->take(10)
            ->get();
        $peakHours = LiveTime::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->selectRaw("FROM_UNIXTIME(start_time, '%H') as hour, COUNT(*) as total_sessions, SUM(hours) as total_duration")
            ->whereRaw("DATE(FROM_UNIXTIME(start_time)) = CURDATE()")
            ->groupBy('hour')
            ->orderByDesc('total_sessions')
            ->limit(1)
            ->first();
        $messagesToday = ChatMessage::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->whereDate('created_at', today())
            ->count();
        $messagesThisMonth = ChatMessage::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersWhoSend = ChatMessage::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->distinct('user_id')
            ->count('user_id');
        $totalUsers = User::whereIn('country_id', $countries)->count();
        $usersWhoNeverSend = $totalUsers - $usersWhoSend;
        $openConversationsToday = ChatMessage::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->whereDate('created_at', today())
            ->distinct('chat_room_id')
            ->count('chat_room_id');
        $avgConversationDuration = ChatMessage::whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->selectRaw('chat_room_id, TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration')
            ->groupBy('chat_room_id')
            ->pluck('duration')
            ->avg() ?? 0;

        // game

        $game = CoinGameUserDailyAggregated::query()->whereHas('user', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })->selectRaw("
            SUM(total_played) as total_played,
            SUM(total_loss) as total_loss,
            SUM(total_win) as total_win,
            SUM(total_loss - total_win) as app_profit
        ")->first();


        //rooms
        $roomCounts = Room::whereHas('owner.country', function ($q) use ($countries) {
            $q->whereIn('id', $countries);
        })
            ->whereHas('roomVisitors')
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->pluck('total', 'type');
        $totalRoomsJoined = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->withCount('roomVisitors')
            ->get()
            ->sum('room_visitors_count');
        $totalRooms = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })->count();
        $longestActiveRoom = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
            ->with(['roomVisitors' => function ($q) {
                $q->select('id', 'room_id', 'created_at');
            }])
            ->get()
            ->map(function ($room) {
                $min = $room->roomVisitors->min('created_at');
                $max = $room->roomVisitors->max('created_at');

                if (!$min || !$max) {
                    return 0;
                }

                return Carbon::parse($max)->diffInDays(Carbon::parse($min));
            })
            ->max() ?? 0;
        $liveRooms = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->where('type', 'live')
            ->selectRaw('is_live, COUNT(*) as total')
            ->groupBy('is_live')
            ->pluck('total', 'is_live');

        $liveRoomsTrue = $liveRooms[1] ?? 0;
        $liveRoomsFalse = $liveRooms[0] ?? 0;
        $mostVisitedRoom = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->withCount('roomVisitors')
            ->orderByDesc('room_visitors_count')
            ->first();
        $mostVisitedRoomCount = $mostVisitedRoom?->room_visitors_count ?? 0;
        $avgVisitorsPerRoom = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->withCount('roomVisitors')
            ->get()
            ->avg('room_visitors_count');

        $avgMicPerRoom = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
            ->pluck('microphone')
            ->filter()
            ->map(fn($mics) => count(array_filter(explode(',', $mics))))
            ->avg();
        //return back another way
        //        $topMicRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
        //            ->get()
        //            ->map(function ($room) {
        //                $micCount = count(array_filter(explode(',', $room->microphone ?? '')));
        //                return [
        //                    'room' => $room,
        //                    'mic_count' => $micCount
        //                ];
        //            })
        //            ->sortByDesc('mic_count')
        //            ->take(10);
        $roomsWithMic = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
            ->whereNotNull('microphone')
            ->where('microphone', '!=', '')
            ->count();
        $percentageWithMic = $totalRooms > 0 ? ($roomsWithMic / $totalRooms) * 100 : 0;

        //agencies
        $agencyCount = Agency::whereIn('country_id', $countries)->count();
        $user_salaries = UserSallary::query()
            ->whereHas('user', function ($q) use ($countries) {
                $q->where('agency_id', '!=', 0)
                    ->whereIn('country_id', $countries)
                    ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries));
            })
            ->sum(DB::raw('sallary - cut_amount'));
        $agency_salaries = AgencySallary::query()->whereHas('agency', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })->sum(DB::raw('sallary - cut_amount'));

        $activeAgencies = Agency::whereIn('country_id', $countries)
            ->whereHas('agencySalaries', fn($q) => $q->where('month', now()->month)
                ->where('year', now()->year))
            ->count();
        $newAgenciesToday = Agency::whereIn('country_id', $countries)->whereDate('created_at', today())->count();
        $newAgenciesMonth = Agency::whereIn('country_id', $countries)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $topAgencies = Agency::whereIn('country_id', $countries)
            ->orderByDesc('coins')
            ->take(10)
            ->get(['id', 'name', 'coins']);
        $avgAgencyWallet = Agency::whereIn('country_id', $countries)->avg('coins');
        $totalMembers = User::whereIn('country_id', $countries)
            ->where('agency_id', '!=', 0)
            ->whereHas('agency', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            })
            ->count();
        $avgMembersPerAgency = $agencyCount > 0 ? $totalMembers / $agencyCount : 0;
        $pendingJoins = Agency::whereIn('country_id', $countries)
            ->whereHas('joinRequests', fn($q) => $q->where('status', 1))
            ->count();
        $diamondsAchieved = UserSallary::whereHas('user', fn($q) => $q->where('country_id', $countries))
            ->whereHas('agency', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            })
            ->sum('achieved_diamond');

        //others
        $bdCount = Bd::whereIn('parent_id', $superAdmins)->whereIn('country_id', $countries)->count();
        $diAuth = Auth::user()->di;
        $totals = Charge::selectRaw("
                SUM(CASE WHEN user_type = ? AND user_id = ? THEN amount ELSE 0 END) as total_charges,
                SUM(CASE WHEN charger_type = ? AND charger_id = ? THEN amount ELSE 0 END) as total_spent
            ", [
            UserTypeEnum::AREA_MANAGER,
            Auth::user()->id,
            UserTypeEnum::AREA_MANAGER,
            Auth::user()->id
        ])
            ->first();

        $totalCharges = $totals->total_charges;
        $totalSpent   = $totals->total_spent;

        $totalSalaries = BD::whereIn('parent_id', $superAdmins)->whereIn('country_id', $countries)
            ->withSum('salaries', 'salary')
            ->withSum('salaries', 'cut_amount')
            ->withCount('agencies')
            ->get();

        $totalBDSalary = $totalSalaries->sum('salaries_sum_salary');
        $totalBDCut = $totalSalaries->sum('salaries_sum_cut_amount');
        $averageAgenciesPerBD = $totalSalaries->avg('agencies_count');

        return parent::index($content
            ->title(__('Home'))
            ->description(__('General Statistics'))

            ->row(function (Row $row) use ($totalCharges, $totalSpent, $agencyCount, $usersCount, $bdCount, $onlineUser, $diAuth, $roomCounts, $agency_salaries, $user_salaries, $countries, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration, $liveRooms, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved, $liveRoomsTrue, $liveRoomsFalse, $topUsersByFollowers, $totalBDSalary, $totalBDCut, $averageAgenciesPerBD, $game) {
                $row->column(4, new InfoBox(__('you Wallet'), 'money', 'green', '', $diAuth . '💎'));
                $row->column(4, new InfoBox(__('total charges'), 'money', 'green', '', truncateAndTrim($totalCharges, 2) . ' 💰'));
                $row->column(4, new InfoBox(__('total spent'), 'money', 'red', '', truncateAndTrim($totalSpent, 2)));
                $row->column(12, function ($column) use ($usersCount, $onlineUser, $countries, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration, $topUsersByFollowers) {
                    $column->row("<h3 style='margin:10px 0;'>👤 " . __('Users') . "</h3>");

                    $column->row(function (Row $row) use ($usersCount, $onlineUser, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration) {
                        $row->column(3, new InfoBox(__('Users Count'), 'users', 'aqua', 'areaManager/users', $usersCount));
                        $row->column(3, new InfoBox(__('Online Users Count'), 'user', 'blue', 'areaManager/users?online=1', $onlineUser));
                        if ($peakHours) {
                            $time = Carbon::createFromTime($peakHours->hour);
                            $time->locale(app()->getLocale());
                            $peakHour = $time->isoFormat('h A');
                            $peakHourCount = $peakHours->total_sessions;
                            $value = $peakHour . ' • ' . $peakHourCount . ' ' . __('Users');
                        } else {
                            $value = 0;
                        }
                        $row->column(3, new InfoBox(__('Peak Hour'), 'clock-o', 'green', 'areaManager/users', $value));
                        $row->column(3, new InfoBox(__('New Sign Ups Today'), 'user-plus', 'yellow', 'areaManager/users?signups=today', $newSignUpsToday));
                        $row->column(3, new InfoBox(__('New Sign Ups This Week'), 'users', 'red', 'areaManager/users?signups=week', $newSignUpsThisWeek));
                        $row->column(3, new InfoBox(__('New Sign Ups This Month'), 'user', 'purple', 'areaManager/users?signups=month', $newSignUpsThisMonth));
                        $row->column(3, new InfoBox(__('Messages Today'), 'envelope', 'maroon', 'areaManager/users?messages=today', $messagesToday));
                        $row->column(3, new InfoBox(__('Messages This Month'), 'comments', 'teal', 'areaManager/users?messages=month', $messagesThisMonth));
                        $row->column(3, new InfoBox(__('Users Who Send Messages'), 'user', 'gray', 'areaManager/users?sent_messages=1', $usersWhoSend));
                        $row->column(3, new InfoBox(__('Users Who Never Send'), 'user-times', 'orange', 'areaManager/users?never_send=1', $usersWhoNeverSend));
                        $row->column(3, new InfoBox(__('Open Conversations Today'), 'comments-o', 'lime', 'areaManager/users', $openConversationsToday));
                        $row->column(3, new InfoBox(__('Avg Conversation Duration (min)'), 'clock-o', 'olive', 'areaManager/users', round($avgConversationDuration)));
                    });

                    $column->row(function (Row $row) use ($countries, $topUsersByFollowers) {
                        // Right: chart view (Top Salaries)
                        $row->column(6, function ($column) use ($countries) {
                            $topUsersByLiveTime = LiveTime::query()
                                ->whereHas('user', function ($q) use ($countries) {
                                    $q->whereIn('country_id', $countries);
                                })
                                ->selectRaw('uid, SUM(hours) as total_hours, COUNT(DISTINCT DATE(created_at)) as active_days')
                                ->groupBy('uid')
                                ->havingRaw('SUM(hours) >= 1')
                                ->orderByDesc('total_hours')
                                ->take(10)
                                ->get();

                            $labels = User::whereIn('id', $topUsersByLiveTime->pluck('uid'))->whereIn('country_id', $countries)->pluck('name');
                            $data   = $topUsersByLiveTime->pluck('total_hours');

                            $view = view('admin.widgets.users_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        // Left: top 10 salaries
                        $row->column(6, function ($column) use ($countries, $topUsersByFollowers) {

                            $view = view('admin.widgets.top_users_visits_chart')->render();

                            $column->row($view);
                        });

                        $row->column(6, function ($column) use ($countries) {
                            $currMonth = now()->month;
                            $prevMonth = now()->subMonth()->month;

                            $signups = User::whereIn('country_id', $countries)
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
                                $dataCurrent[]  = $signups->where('month', $currMonth)->where('week_of_month', $week)->sum('total');
                                $dataPrevious[] = $signups->where('month', $prevMonth)->where('week_of_month', $week)->sum('total');
                            }

                            $view = view('admin.widgets.signups_weekly_chart', [
                                'labels'       => $labels,
                                'dataCurrent'  => $dataCurrent,
                                'dataPrevious' => $dataPrevious,
                            ])->render();

                            $column->row($view);
                        });

                        $row->column(6, function ($column) {
                            $view = view('admin.widgets.peak_hours_card')->render();
                            $column->row($view);
                        });
                    });
                });

                $row->column(12, function ($column) use ($countries, $topUsersByFollowers) {
                    $column->row(function (Row $row) use ($countries, $topUsersByFollowers) {
                        $row->column(6, function ($col) use ($topUsersByFollowers) {
                            $top5 = $topUsersByFollowers
                                ->filter(fn($user) => $user->followers_count > 0)
                                ->take(5);

                            $view5 = view('admin.widgets.top_followers_table', [
                                'top5' => $top5,
                            ])->render();

                            $col->row($view5);
                        });

                        $row->column(6, function ($col) use ($countries) {
                            $view = view('admin.widgets.users_online_chart')->render();
                            $col->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($countries, $roomCounts, $totalRoomsJoined, $liveRooms, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic, $liveRoomsTrue, $liveRoomsFalse,) {
                    $column->row("<h3 style='margin:10px 0;'>🏠 " . __('Rooms') . "</h3>");

                    $column->row(function (Row $row) use ($roomCounts, $totalRoomsJoined, $liveRoomsTrue, $liveRoomsFalse, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic) {
                        $row->column(3, new InfoBox(__('Audio Rooms'), 'headphones', 'blue', 'areaManager/rooms?online=1', $roomCounts['audio'] ?? 0));
                        $row->column(3, new InfoBox(__('Live Rooms'), 'microphone', 'green', 'areaManager/live-rooms?online=1', $roomCounts['live'] ?? 0));
                        //                        $row->column(3, new InfoBox(__('Total Rooms Joined By Visitors'), 'building', 'yellow', 'areaManager/rooms', $totalRoomsJoined));
                        //                        $row->column(3, new InfoBox(__('Top Room Messages'), 'commenting', 'teal', 'areaManager/rooms/' . ($topRoom ? $topRoom->id : '#'), $topRoom ? $topRoom->messages_count : 0));
                        $row->column(3, new InfoBox(__('Live Rooms'), 'microphone', 'purple', 'areaManager/live-rooms?online=1', $roomCounts['live'] ?? 0));
                        //                        $row->column(3, new InfoBox(__('Total Rooms Joined By Visitors'), 'building', 'yellow', 'areaManager/rooms', $totalRoomsJoined));
                        //                        $row->column(3, new InfoBox(__('Top Room Messages'), 'commenting', 'teal', 'areaManager/rooms/' . ($topRoom ? $topRoom->id : '#'), $topRoom ? $topRoom->messages_count : 0));
                        $row->column(3, new InfoBox(__('Live Rooms (Active)'), 'microphone', 'green', 'areaManager/live-rooms?is_live=1', $liveRoomsTrue));
                        $row->column(3, new InfoBox(__('Live Rooms (Inactive)'), 'microphone-slash', 'red', 'areaManager/live-rooms?is_live=0', $liveRoomsFalse));
                        //                        $row->column(3, new InfoBox(__('Most Visited Room (visitors)'), 'users', 'lime', 'areaManager/rooms', $mostVisitedRoomCount));
                        //                        $row->column(3, new InfoBox(__('Avg Visitors Per Room'), 'user-plus', 'gray', 'areaManager/rooms', round($avgVisitorsPerRoom, 2)));
                        //                        $row->column(3, new InfoBox(__('Longest Active Room (days)'), 'clock-o', 'yellow', 'areaManager/rooms', $longestActiveRoom));
                        //                        $row->column(3, new InfoBox(__('Avg Mic Users per Room'), 'users', 'purple', 'areaManager/rooms', round($avgMicPerRoom, 2)));
                        //                        $row->column(3, new InfoBox(__('Rooms With Mic Usage'), 'volume-up', 'maroon', 'areaManager/rooms', $roomsWithMic));
                        //                        $row->column(3, new InfoBox(__('Rooms With Mic (%)'), 'pie-chart', 'teal', 'areaManager/rooms', round($percentageWithMic, 1) . '%'));
                    });

                    $column->row(function (Row $row) use ($countries) {
                        //chart 1
                        $row->column(6, function ($column) use ($countries) {
                            $roomsWithPk = Room::whereHas('owner', function ($q) use ($countries) {
                                $q->whereIn('country_id', $countries);
                            })
                                ->has('lastPk')
                                ->count();

                            $audioRooms = Room::whereHas('owner', function ($q) use ($countries) {
                                $q->whereIn('country_id', $countries);
                            })
                                ->where('type', 'audio')
                                ->count();

                            $liveRooms = Room::whereHas('owner', function ($q) use ($countries) {
                                $q->whereIn('country_id', $countries);
                            })
                                ->where('type', 'live')
                                ->count();

                            $inactiveRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
                                ->whereDoesntHave('roomVisitors')
                                ->count();

                            $roomStats = [
                                __('Rooms with PK')  => $roomsWithPk,
                                __('Audio Rooms')    => $audioRooms,
                                __('Live Rooms')     => $liveRooms,
                                __('Inactive Rooms') => $inactiveRooms,
                            ];

                            $view = view('admin.widgets.rooms_distribution_chart', [
                                'labels' => array_keys($roomStats),
                                'data'   => array_values($roomStats),
                            ])->render();

                            $column->row($view);
                        });

                        //chart2
                        $row->column(6, function ($column) {
                            $view = view('admin.widgets.rooms_activity_chart')->render();
                            $column->row($view);
                        });

                        //chart 3
                        $row->column(6, function ($column) use ($countries) {
                            $topGiftedRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
                                ->with('owner')
                                ->withSum('gifts', 'giftPrice')
                                ->orderByDesc('gifts_sum_gift_price')
                                ->take(10)
                                ->get()
                                ->filter(fn($room) => $room->gifts_sum_gift_price > 0);

                            $labels = $topGiftedRooms->map(fn($room) => $room->owner->name ?? 'Unknown');
                            $data   = $topGiftedRooms->pluck('gifts_sum_gift_price');

                            $view = view('admin.widgets.top_gifted_rooms_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        //chart4
                        $row->column(6, function ($column) use ($countries) {
                            $avgSessionRooms = \DB::table('rooms')
                                ->join('users', 'rooms.uid', '=', 'users.id')
                                ->join('live_times', 'users.id', '=', 'live_times.uid')
                                ->whereIn('users.country_id', $countries)
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
                            $data   = $avgSessionRooms->pluck('avg_duration');
                            $view = view('admin.widgets.avg_session_duration_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($countries, $agencyCount, $agency_salaries, $user_salaries, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved) {
                    $column->row("<h3 style='margin:10px 0;'>🏢 " . __('Agencies') . "</h3>");

                    $column->row(function (Row $row) use ($agencyCount, $agency_salaries, $user_salaries, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved) {
                        $row->column(3, new InfoBox(__('Agencies Count'), 'building', 'olive', 'areaManager/agencies', $agencyCount));
                        $row->column(3, new InfoBox(__('total agency salary'), 'building', 'lime', 'areaManager/agencies', round($agency_salaries)));
                        $row->column(3, new InfoBox(__('Total Users Salary'), 'money', 'gray', 'areaManager/ag/users', round($user_salaries)));
                        $row->column(3, new InfoBox(__('Active Agencies'), 'building', 'red', 'areaManager/agencies?active=true', $activeAgencies));
                        $row->column(3, new InfoBox(__('New Agencies Today'), 'plus', 'teal', 'areaManager/agencies?created=today', $newAgenciesToday));
                        $row->column(3, new InfoBox(__('New Agencies This Month'), 'calendar', 'orange', 'areaManager/agencies?created=month', $newAgenciesMonth));
                        $row->column(3, new InfoBox(__('Average Agency Wallet'), 'money', 'aqua', 'areaManager/agencies', round($avgAgencyWallet)));
                        $row->column(3, new InfoBox(__('Total Members in Agencies'), 'users', 'maroon', 'areaManager/users?agencyMembers=1', $totalMembers));
                        $row->column(3, new InfoBox(__('Avg Members Per Agency'), 'user', 'lime', 'areaManager/agencies', round($avgMembersPerAgency)));
                        $row->column(3, new InfoBox(__('Pending Join Requests'), 'hourglass', 'purple', 'areaManager/agencies?pending=1', $pendingJoins));
                        $row->column(3, new InfoBox(__('Diamonds Achieved by Hosts'), 'diamond', 'green', 'areaManager/ag/users', $diamondsAchieved));
                    });

                    $column->row(function (Row $row) use ($countries) {
                        //chart 1
                        $row->column(6, function ($column) use ($countries) {
                            $topAgenciesByTargets = UserTarget::whereHas('agency', fn($q) => $q->whereIn('country_id', $countries))
                                //                                ->where('add_month', now()->month)
                                //                                ->where('add_year', now()->year)
                                ->where('agency_obtain', '>', 0)
                                ->selectRaw('agency_id, COUNT(*) as total_achieved')
                                ->groupBy('agency_id')
                                ->orderByDesc('total_achieved')
                                ->with('agency:id,name')
                                ->take(10)
                                ->get();

                            $labels = $topAgenciesByTargets->map(fn($t) => $t->agency->name ?? 'Unknown');
                            $data   = $topAgenciesByTargets->pluck('total_achieved');

                            $view = view('admin.widgets.agencies_targets_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        //chart 2
                        $row->column(6, function ($column) use ($countries) {
                            $topSenders = GiftLog::whereHas(
                                'sender',
                                fn($q) =>
                                $q->whereIn('country_id', $countries)
                                    ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries))
                            )
                                ->selectRaw('sender_id, SUM(giftPrice) as total_sent')
                                ->groupBy('sender_id')
                                ->orderByDesc('total_sent')
                                ->take(10)
                                ->with('sender:id,name')
                                ->get()
                                ->filter(fn($s) => $s->total_sent > 0);

                            $labels = $topSenders->map(fn($s) => $s->sender->name ?? 'Unknown');
                            $data   = $topSenders->pluck('total_sent');

                            $view = view('admin.widgets.top_senders_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        //chart 3
                        $row->column(6, function ($column) use ($countries) {
                            $topReceivers = GiftLog::whereHas(
                                'receiver',
                                fn($q) =>
                                $q->whereIn('country_id', $countries)
                                    ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries))
                            )
                                ->selectRaw('receiver_id, SUM(giftPrice) as total_received')
                                ->groupBy('receiver_id')
                                ->orderByDesc('total_received')
                                ->take(10)
                                ->with('receiver:id,name')
                                ->get()
                                ->filter(fn($s) => $s->total_received > 0);

                            $labels = $topReceivers->map(fn($r) => $r->receiver->name ?? 'Unknown');
                            $data   = $topReceivers->pluck('total_received');

                            $view = view('admin.widgets.top_receivers_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        //chart 4
                        $row->column(6, function ($column) use ($countries) {
                            $achievedAgencies = Agency::whereIn('country_id', $countries)
                                ->whereHas('userTarget', function ($q) {
                                    $q
                                        //                                        ->where('add_month', now()->month)
                                        //                                        ->where('add_year', now()->year)
                                        ->where('agency_obtain', '>', 0);
                                })
                                ->count();

                            $notAchievedAgencies = Agency::whereIn('country_id', $countries)
                                ->count() - $achievedAgencies;

                            $view = view('admin.widgets.agencies_compare_chart', [
                                'achieved'    => $achievedAgencies,
                                'notAchieved' => $notAchievedAgencies,
                            ])->render();

                            $column->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($bdCount, $totalBDSalary, $totalBDCut, $averageAgenciesPerBD) {
                    $column->row("<h3 style='margin:10px 0;'>💼 " . __('BD') . "</h3>");

                    $column->row(function (Row $row) use ($bdCount, $totalBDSalary, $totalBDCut, $averageAgenciesPerBD) {
                        $row->column(3, new InfoBox(__('Bd Count'), 'briefcase', 'aqua', 'areaManager/usersBD', $bdCount));
                        $row->column(3, new InfoBox(__('Total BD Salary'), 'wallet', 'green', 'areaManager/bd-salaries', number_format($totalBDSalary)));
                        $row->column(3, new InfoBox(__('Total Cut Amount'), 'money-bill-wave', 'red', 'areaManager/bd-salaries', number_format($totalBDCut)));
                        $row->column(3, new InfoBox(__('Average Agencies Per BD'), 'briefcase', 'aqua', 'areaManager/usersBD', number_format($averageAgenciesPerBD)));
                    });
                });

                $row->column(12, function ($column) use ($game) {
                    $column->row("<h3 style='margin:10px 0;'>💼 " . __('game') . "</h3>");

                    $column->row(function (Row $row) use ($game) {
                        $row->column(3, new InfoBox(__('Total Played'), 'gamepad', 'blue', "", number_format($game->total_played ?? 0, 2)));
                        //                        $row->column(3, new InfoBox(__('Total Loss'), 'times-circle', 'red',"", number_format($game->total_loss ?? 0, 2)));
                        //                        $row->column(3, new InfoBox(__('Total Win'), 'trophy', 'orange',"", number_format($game->total_win ?? 0, 2)));
                        //                        $row->column(3, new InfoBox(__('App Profit'), 'dollar', 'green',"", number_format($game->app_profit ?? 0, 2)));
                    });
                });
            }));
    }

    public function getTopFollowers(Request $request): JsonResponse
    {
        $topUsersByFollowers = User::withCount('followers')
            ->with('packs', 'profile')
            ->whereIn('country_id', $this->countries())
            ->orderByDesc('followers_count')
            ->take(10)
            ->get();

        return response()->json($topUsersByFollowers);
    }

    public function peakHours(Request $request): JsonResponse
    {
        $period = $request->get('period', 'day');

        $query = DB::table('live_times')
            ->join('users', 'live_times.uid', '=', 'users.id')
            ->whereIn('users.country_id', $this->countries());

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
            'data'   => $rows->pluck('total'),
        ]);
    }

    public function onlineStats(): JsonResponse
    {
        $countries = $this->countries();

        $online  = User::whereIn('country_id', $countries)->where('online', 1)->count();
        $offline = User::whereIn('country_id', $countries)->where('online', 0)->count();


        return response()->json(data: [
            'online'  => $online,
            'offline' => $offline,
        ]);
    }
    public function roomsActivity(Request $request): JsonResponse
    {
        $period = $request->get('period', 'day');
        $countries = $this->countries();

        if ($period === 'day') {
            // last 7 days
            $dates = collect(range(0, 6))
                ->map(fn($i) => now()->subDays($i)->format('Y-m-d'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $newRooms = $dates->map(fn($d) => $newRoomsData[$d] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->whereIn('users.country_id', $countries)
                ->whereDate('live_times.created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(live_times.created_at) as date, COUNT(DISTINCT users.id) as active_owners')
                ->groupBy('date')
                ->pluck('active_owners', 'date');

            $totalRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))->count();

            $inactiveRooms = $dates->map(fn($d) => $totalRooms - ($activeOwners[$d] ?? 0));

            $labels = $dates;
        } elseif ($period === 'week') {
            // last 4 weeks
            $weeks = collect(range(0, 3))
                ->map(fn($i) => now()->subWeeks($i)->format('o-\WW'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
                ->where('created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(created_at) as year, WEEK(created_at,1) as week, COUNT(*) as total")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->total]);

            $newRooms = $weeks->map(fn($w) => $newRoomsData[$w] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->whereIn('users.country_id', $countries)
                ->whereDate('live_times.created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(live_times.created_at) as year, WEEK(live_times.created_at,1) as week, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->active_owners]);

            $totalRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))->count();

            $inactiveRooms = $weeks->map(fn($w) => $totalRooms - ($activeOwners[$w] ?? 0));

            $labels = $weeks;
        } elseif ($period === 'month') {
            // last 6 months
            $months = collect(range(0, 5))
                ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
                ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym');

            $newRooms = $months->map(fn($m) => $newRoomsData[$m] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->whereIn('users.country_id', $countries)
                ->where('live_times.created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(live_times.created_at,'%Y-%m') as ym, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('ym')
                ->pluck('active_owners', 'ym');

            $totalRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))->count();

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

    public function topUsersData(Request $request): JsonResponse
    {
        $countries = $this->countries();

        $topUsers = LiveTime::query()
            ->whereHas('user', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            })
            ->selectRaw('uid, SUM(hours) as total_hours, COUNT(DISTINCT DATE(created_at)) as active_days')
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
            'data'   => $data
        ]);
    }

    public function topUsersVisits(Request $request): JsonResponse
    {
        $countries = $this->countries();

        // Performance fix: replaced correlated subquery (withCount) with JOIN
        // Old query caused full table scan on live_times (74K rows) per user → 1+ hour stuck queries
        $topUsers = User::select('users.id', 'users.name', DB::raw('SUM(live_times.hours) as total_hours'))
            ->join('live_times', 'users.id', '=', 'live_times.uid')
            ->whereIn('users.country_id', $countries)
            ->where('live_times.start_time', '>=', now()->subMonth()->timestamp)
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.name')
            ->having('total_hours', '>', 0)
            ->orderByDesc('total_hours')
            ->take(10)
            ->get();

        return response()->json([
            'labels' => $topUsers->pluck('name'),
            'data'   => $topUsers->pluck('total_hours')
        ]);
    }

    protected function comparisonUserSignUp(): JsonResponse
    {
        $currMonth = now()->month;
        $prevMonth = now()->subMonth()->month;
        $countries = $this->countries();

        $signups = User::whereIn('country_id', $countries)
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
            $dataCurrent[]  = $signups->where('month', $currMonth)->where('week_of_month', $week)->sum('total');
            $dataPrevious[] = $signups->where('month', $prevMonth)->where('week_of_month', $week)->sum('total');
        }

        $currMonthName = Carbon::create()->month($currMonth)->translatedFormat('F');
        $prevMonthName = Carbon::create()->month($prevMonth)->translatedFormat('F');

        return response()->json([
            'labels'       => $labels,
            'dataCurrent'  => $dataCurrent,
            'dataPrevious' => $dataPrevious,
            'currentMonth'  => $currMonthName,
            'previousMonth' => $prevMonthName,
        ]);
    }

    protected function distributionRooms(): JsonResponse
    {
        $countries = $this->countries();

        $roomsWithPk = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->has('lastPk')
            ->count();

        $audioRooms = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->where('type', 'audio')
            ->count();

        $liveRooms = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->where('type', 'live')
            ->count();

        $inactiveRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
            ->whereDoesntHave('roomVisitors')
            ->count();

        $roomStats = [
            __('Rooms with PK')  => $roomsWithPk,
            __('Audio Rooms')    => $audioRooms,
            __('Live Rooms')     => $liveRooms,
            __('Inactive Rooms') => $inactiveRooms,
        ];

        return response()->json([
            'labels' => array_keys($roomStats),
            'data'   => array_values($roomStats),
        ]);
    }

    protected function topRoomGifts(): JsonResponse
    {
        $countries = $this->countries();

        $topGiftedRooms = Room::whereHas('owner', fn($q) => $q->whereIn('country_id', $countries))
            ->with('owner')
            ->withSum('gifts', 'giftPrice')
            ->orderByDesc('gifts_sum_gift_price')
            ->take(10)
            ->get()
            ->filter(fn($room) => $room->gifts_sum_gift_price > 0);

        $labels = $topGiftedRooms->map(fn($room) => $room->owner->name ?? 'Unknown');
        $data   = $topGiftedRooms->pluck('gifts_sum_gift_price');
        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    protected function averageActiveRooms(): JsonResponse
    {
        $countries = $this->countries();

        $avgSessionRooms = \DB::table('rooms')
            ->join('users', 'rooms.uid', '=', 'users.id')
            ->join('live_times', 'users.id', '=', 'live_times.uid')
            ->whereIn('users.country_id', $countries)
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
        $data   = $avgSessionRooms->pluck('avg_duration');
        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    protected function agencyTarget(): JsonResponse
    {
        $countries = $this->countries();

        $topAgenciesByTargets = UserTarget::whereHas('agency', fn($q) => $q->whereIn('country_id', $countries))
            ->where('agency_obtain', '>', 0)
            ->selectRaw('agency_id, COUNT(*) as total_achieved')
            ->groupBy('agency_id')
            ->orderByDesc('total_achieved')
            ->with('agency:id,name')
            ->take(10)
            ->get();

        $labels = $topAgenciesByTargets->map(fn($t) => $t->agency->name ?? 'Unknown');
        $data   = $topAgenciesByTargets->pluck('total_achieved');
        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    protected function topSender(): JsonResponse
    {
        $countries = $this->countries();

        $topSenders = GiftLog::whereHas(
            'sender',
            fn($q) =>
            $q->whereIn('country_id', $countries)
                ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries))
        )
            ->selectRaw('sender_id, SUM(giftPrice) as total_sent')
            ->groupBy('sender_id')
            ->orderByDesc('total_sent')
            ->take(10)
            ->with('sender:id,name')
            ->get()
            ->filter(fn($s) => $s->total_sent > 0);

        $labels = $topSenders->map(fn($s) => $s->sender->name ?? 'Unknown');
        $data   = $topSenders->pluck('total_sent');
        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    protected function topReceiver(): JsonResponse
    {
        $countries = $this->countries();

        $topReceivers = GiftLog::whereHas(
            'receiver',
            fn($q) =>
            $q->whereIn('country_id', $countries)
                ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries))
        )
            ->selectRaw('receiver_id, SUM(giftPrice) as total_received')
            ->groupBy('receiver_id')
            ->orderByDesc('total_received')
            ->take(10)
            ->with('receiver:id,name')
            ->get()
            ->filter(fn($s) => $s->total_received > 0);

        $labels = $topReceivers->map(fn($r) => $r->receiver->name ?? 'Unknown');
        $data   = $topReceivers->pluck('total_received');
        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    protected function comparisonAgencyTarget(): JsonResponse
    {
        $countries = $this->countries();

        $achievedAgencies = Agency::whereIn('country_id', $countries)
            ->whereHas('userTarget', function ($q) {
                $q->where('agency_obtain', '>', 0);
            })
            ->count();

        $notAchievedAgencies = Agency::whereIn('country_id', $countries)->count() - $achievedAgencies;

        return response()->json([
            'achieved'    => $achievedAgencies,
            'notAchieved' => $notAchievedAgencies,
        ]);
    }

    public function roomStats(): JsonResponse
    {
        $countries = $this->countries();

        $roomCounts = Room::whereHas('owner.country', function ($q) use ($countries) {
            $q->whereIn('id', $countries);
        })
            ->whereHas('roomVisitors')
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->pluck('total', 'type');

        $liveRooms = Room::whereHas('owner', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })
            ->where('type', 'live')
            ->selectRaw('is_live, COUNT(*) as total')
            ->groupBy('is_live')
            ->pluck('total', 'is_live');

        $liveRoomsTrue = $liveRooms[1] ?? 0;
        $liveRoomsFalse = $liveRooms[0] ?? 0;
        return response()->json([
            'audio'         => $roomCounts['audio'] ?? 0,
            'live'          => $roomCounts['live'] ?? 0,
            'active'        => $liveRoomsTrue,
            'inactive'      =>  $liveRoomsFalse,
        ]);
    }

    public function getStats(): JsonResponse
    {
        $countries = $this->countries();

        $agencyCount = Agency::whereIn('country_id', $countries)->count();

        $user_salaries = UserSallary::query()
            ->whereHas('user', function ($q) use ($countries) {
                $q->where('agency_id', '!=', 0)
                    ->whereIn('country_id', $countries)
                    ->whereHas('agency', fn($a) => $a->whereIn('country_id', $countries));
            })
            ->sum(DB::raw('sallary - cut_amount'));

        $agency_salaries = AgencySallary::query()->whereHas('agency', function ($q) use ($countries) {
            $q->whereIn('country_id', $countries);
        })->sum(DB::raw('sallary - cut_amount'));

        $activeAgencies = Agency::whereIn('country_id', $countries)
            ->whereHas('agencySalaries', fn($q) => $q->where('month', now()->month)
                ->where('year', now()->year))
            ->count();

        $newAgenciesToday = Agency::whereIn('country_id', $countries)->whereDate('created_at', today())->count();

        $newAgenciesMonth = Agency::whereIn('country_id', $countries)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $avgAgencyWallet = Agency::whereIn('country_id', $countries)->avg('coins');

        $totalMembers = User::whereIn('country_id', $countries)
            ->where('agency_id', '!=', 0)
            ->whereHas('agency', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            })
            ->count();
        $avgMembersPerAgency = $agencyCount > 0 ? $totalMembers / $agencyCount : 0;

        $pendingJoins = Agency::whereIn('country_id', $countries)
            ->whereHas('joinRequests', fn($q) => $q->where('status', 1))
            ->count();

        $diamondsAchieved = UserSallary::whereHas('user', fn($q) => $q->where('country_id', $countries))
            ->whereHas('agency', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            })
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

    public function getBdStats(): JsonResponse
    {
        $countries = $this->countries();
        $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;
        $superAdmins = SuperAdmin::where('parent_id', $authId)->pluck('id')->toArray();

        $bdCount = Bd::whereIn('parent_id', $superAdmins)->whereIn('country_id', $countries)->count();

        $totalSalaries = BD::whereIn('parent_id', $superAdmins)->whereIn('country_id', $countries)
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

    //TODO need to be filtered
    public function getBalanceData(Request $request)
    {
        try {
            $date = $request->get("date");

            $balanceQuery = GameWallet::query();
            $balanceDollarQuery = GameChargeHistory::query();

            if ($date != null) {
                $year = substr($date, 0, 4);
                $month = substr($date, 5, 2);
                $balanceQuery->whereMonth("created_at", $month)->whereYear("created_at", $year);
                $balanceDollarQuery->whereMonth("created_at", $month)->whereYear("created_at", $year);
            } else {
                $balanceQuery->whereMonth("created_at", date("m"))->whereYear("created_at", date("Y"));
                $balanceDollarQuery->whereMonth("created_at", date("m"))->whereYear("created_at", date("Y"));
            }

            $balance = $balanceQuery->first();
            $balanceDollar = $balanceDollarQuery->sum("value");
            $allBalance = $balance->balance ?? 0;
            $availableBalance = $balance ? $balance->balance - $balance->used : 0;

            $used = $balance->used ?? 0;
            $available = $availableBalance ?? 0;

            $chartData = [$used, $available];
            $usePercentage = ($allBalance > 0) ? (($used / $allBalance) * 100) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'allBalance' => $allBalance,
                    'availableBalance' => $available,
                    'balanceDollar' => $balanceDollar,
                    'used' => $used,
                    'usePercentage' => $usePercentage,
                    'chartData' => $chartData,
                    'showPaymentAlert' => (($used > 0) && ($usePercentage <= 90)) ? 1 : 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching balance data'
            ], 500);
        }
    }

    /**
     * Country scope for the game endpoints: this area-manager's countries,
     * optionally narrowed by an explicit ?country_id= override (honored only when
     * it belongs to the manager's countries, so isolation is never widened).
     * Always returns a non-empty array — game stats are never global here.
     */
    private function gameCountryScope(Request $request): array
    {
        $base = array_values(array_map('intval', (array) $this->countries()));
        $requested = (int) $request->get('country_id');
        if ($requested) {
            return in_array($requested, $base, true) ? [$requested] : [0];
        }
        return empty($base) ? [0] : $base;
    }

    public function gameSummary(Request $request): JsonResponse
    {
        $countryID = $this->gameCountryScope($request);
        $cacheKey = 'game_tab:summary:' . md5(json_encode($countryID));

        $data = Cache::remember($cacheKey, 120, function () use ($countryID) {
            $total = CoinGameUserDailyAggregated::query()
                ->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID))
                ->selectRaw("
                    SUM(total_played) as total_played,
                    SUM(total_loss)   as total_loss,
                    SUM(total_win)    as total_win,
                    SUM(total_loss - total_win) as app_profit
                ")
                ->first();

            $today = DB::table('coin_game_users as cgu')
                ->leftJoin('users as u', 'u.id', '=', 'cgu.user_id')
                ->whereDate('cgu.created_at', today())
                ->whereIn('u.country_id', $countryID)
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
                'app_profit' => (int) ($total->app_profit ?? 0),
                'total'      => [
                    'total_played' => (int) ($total->total_played ?? 0),
                    'total_loss'   => (int) ($total->total_loss ?? 0),
                    'total_win'    => (int) ($total->total_win ?? 0),
                    'app_profit'   => (int) ($total->app_profit ?? 0),
                ],
                'today'      => [
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
     * all-time from the indexed daily aggregate. game_id may hold all_games.id OR
     * all_games.custom_id, so both are joined and name/image COALESCE'd (canonical
     * resolution — see CoinGameUserService::buildGrid_details()).
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
                ->whereIn('u.country_id', $countryID)
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
                ->whereIn('u.country_id', $countryID)
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

            $map = fn($row) => [
                'game_id'      => $row->game_id,
                'game_name'    => $row->game_name ?: ('#' . $row->game_id),
                'game_image'   => getImagePath($row->game_image) ?: asset('images/businessman-icon.jpg'),
                'total_played' => (int) $row->total_played,
            ];

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
                ->whereIn('u.country_id', $countryID)
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
                ->map(fn($row) => [
                    'user_id'      => $row->user_id,
                    'name'         => $row->name ?: ('#' . $row->user_id),
                    'avatar_url'   => $row->avatar ? getImagePath($row->avatar) : asset('images/businessman-icon.jpg'),
                    'total_played' => (int) $row->total_played,
                ])
                ->values();
        });

        return response()->json($data);
    }

    public function getStatsData(Request $request): JsonResponse
    {
        try {
            $countries = $this->countries();

            $userBaseQuery = User::whereIn('country_id', $countries);

            $stats = [
                'usersCount' => (clone $userBaseQuery)->count(),
                'newSignUpsToday' => (clone $userBaseQuery)->whereDate('created_at', today())->count(),
                'newSignUpsThisWeek' => (clone $userBaseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'newSignUpsThisMonth' => (clone $userBaseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'onlineUser' => (clone $userBaseQuery)->where('online', 1)->count(),
            ];

            $peakHours = LiveTime::whereHas('user', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);;
            })
                ->selectRaw("FROM_UNIXTIME(start_time, '%H') as hour, COUNT(*) as total_sessions, SUM(hours) as total_duration")
                ->whereRaw("DATE(FROM_UNIXTIME(start_time)) = CURDATE()")
                ->groupBy('hour')
                ->orderByDesc('total_sessions')
                ->limit(1)
                ->first();

            if ($peakHours) {
                $time = Carbon::createFromTime($peakHours->hour);
                $time->locale(app()->getLocale());
                $stats['peakHour'] = $time->isoFormat('h A') . ' • ' . $peakHours->total_sessions . ' ' . __('Users');
            } else {
                $stats['peakHour'] = '0';
            }

            $chatMessageQuery = ChatMessage::whereHas('user', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);;
            });

            $stats['messagesToday'] = (clone $chatMessageQuery)->whereDate('created_at', today())->count();
            $stats['messagesThisMonth'] = (clone $chatMessageQuery)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count();

            $stats['usersWhoSend'] = (clone $chatMessageQuery)->distinct('user_id')->count('user_id');
            $stats['usersWhoNeverSend'] = $stats['usersCount'] - $stats['usersWhoSend'];

            $stats['openConversationsToday'] = (clone $chatMessageQuery)->whereDate('created_at', today())
                ->distinct('chat_room_id')->count('chat_room_id');

            $stats['avgConversationDuration'] = ChatMessage::whereHas('user', function ($q) use ($countries) {
                $q->whereIn('country_id', $countries);;
            })
                ->selectRaw('chat_room_id, TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration')
                ->groupBy('chat_room_id')
                ->pluck('duration')
                ->avg() ?? 0;

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
        $countries = $this->countries();

        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $result = UserSallary::whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
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

        $totalCharges = Charge::whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->sum('usd');

        $totalPayments = CoinLog::whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->sum('obtained_coins');

        $totalGiftsValue = GiftLog::whereHas('sender', fn($q) => $q->whereIn('country_id', $countries))
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
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
        $countries = $this->countries();

        $payments = CoinLog::with('coin.paymentGateway')
            ->whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->whereIn('status', [1, 2])
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'gateway' => $p->coin->paymentGateway->title ?? '',
                'amount' => $p->obtained_coins,
                'status' => $p->status,
                'date' => Carbon::parse($p->created_at)->format('Y-m-d')
            ]);

        $withdrawals = WalletLog::with('user.profile')
            ->whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->where('operation', 'subtract')
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

        $topUsers = DB::table('charges')
            ->join('users', 'charges.user_id', '=', 'users.id')
            ->whereIn('users.country_id', $countries)
            ->where('charges.user_type', 'user')
            ->select('charges.user_id', DB::raw('SUM(charges.usd) as total_usd'), DB::raw('MAX(charges.created_at) as last_charge'))
            ->groupBy('charges.user_id')
            ->orderByDesc('total_usd')
            ->limit(5)
            ->get();

        $users = $topUsers->map(function ($u) {
            $user = User::find($u->user_id);

            $defaultImage = asset('images/businessman-icon.jpg');
            $path = $user->profile?->avatar ?? null;
            $url = $path ? getImagePath($path) : $defaultImage;

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
        $countries = $this->countries();

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

        $charges = Charge::whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->selectRaw('DATE(created_at) as date, SUM(usd) as total')
            ->whereBetween('created_at', [$from, $to])
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
        $countries = $this->countries();

        $logs = WalletLog::with('user.profile')
            ->whereHas('user', fn($q) => $q->whereIn('country_id', $countries))
            ->whereIn('operation', ['add', 'cut'])
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

}
