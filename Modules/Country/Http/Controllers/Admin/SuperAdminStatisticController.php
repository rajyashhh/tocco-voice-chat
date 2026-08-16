<?php

namespace Modules\Country\Http\Controllers\Admin;

use App\Admin\Controllers\MainController;
use App\Models\Bd;
use App\Models\Country;
use Modules\Country\Entities\SuperAdmin;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use App\Models\GiftLog;
use App\Models\LiveTime;
use App\Models\UserTarget;
use App\Models\UserSallary;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use App\Models\AgencySallary;
use Encore\Admin\Layout\Content;
use App\Models\AgencyJoinRequest;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Facades\DB;
use App\Enums\Charges\UserTypeEnum;
use App\Admin\Widgets\CustomInfoBox;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Entities\ChatMessage;
use App\Models\CoinGameUserDailyAggregated;

class SuperAdminStatisticController extends MainController
{

    public $permission_name = 'superadmin';

    public function index(Content $content)
    {
        Permission::check('browse-' . $this->permission_name);

        $countryID =session('filter_country_id');

        if (!$countryID) {
            $countryID = Country::first();
        }

        $usersCount = User::where('country_id', $countryID)->count();

        //users
        $newSignUpsToday = User::whereDate('created_at', today())->where('country_id', $countryID)->count();
        $newSignUpsThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('country_id', $countryID)->count();
        $newSignUpsThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('country_id', $countryID)->count();
        $onlineUser = User::where('country_id', $countryID)->where('online', 1)->count();
        $topUsersByFollowers = User::withCount('followers')
            ->with('packs', 'profile')
            ->where('country_id', $countryID)
            ->orderByDesc('followers_count')
            ->take(10)
            ->get();
        $peakHours = LiveTime::whereHas('user', function ($q) use ($countryID) {
                $q->where('country_id', $countryID);
            })
            ->selectRaw("FROM_UNIXTIME(start_time, '%H') as hour, COUNT(*) as total_sessions, SUM(hours) as total_duration")
            ->whereRaw("DATE(FROM_UNIXTIME(start_time)) = CURDATE()")
            ->groupBy('hour')
            ->orderByDesc('total_sessions')
            ->limit(1)
            ->first();
        $messagesToday = ChatMessage::whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->whereDate('created_at', today())
            ->count();
        $messagesThisMonth = ChatMessage::whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersWhoSend = ChatMessage::whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->distinct('user_id')
            ->count('user_id');
        $totalUsers = User::where('country_id', $countryID)->count();
        $usersWhoNeverSend = $totalUsers - $usersWhoSend;
        $openConversationsToday = ChatMessage::whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->whereDate('created_at', today())
            ->distinct('chat_room_id')
            ->count('chat_room_id');
        $avgConversationDuration = ChatMessage::whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->selectRaw('chat_room_id, TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration')
            ->groupBy('chat_room_id')
            ->pluck('duration')
            ->avg() ?? 0;

        // game

        $game = CoinGameUserDailyAggregated::query()->whereHas('user', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })->selectRaw("
            SUM(total_played) as total_played,
            SUM(total_loss) as total_loss,
            SUM(total_win) as total_win,
            SUM(total_loss - total_win) as app_profit
        ")->first();


        //rooms
        $roomCounts = Room::whereHas('owner.country', function ($q) use ($countryID) {
            $q->where('id', $countryID);
        })
            ->whereHas('roomVisitors')
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->pluck('total', 'type');
        $totalRoomsJoined = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->withCount('roomVisitors')
            ->get()
            ->sum('room_visitors_count');
        $totalRooms = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })->count();
        $longestActiveRoom = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
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
        $liveRooms = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->where('type', 'live')
            ->selectRaw('is_live, COUNT(*) as total')
            ->groupBy('is_live')
            ->pluck('total', 'is_live');

        $liveRoomsTrue = $liveRooms[1] ?? 0;
        $liveRoomsFalse = $liveRooms[0] ?? 0;
        $mostVisitedRoom = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->withCount('roomVisitors')
            ->orderByDesc('room_visitors_count')
            ->first();
        $mostVisitedRoomCount = $mostVisitedRoom?->room_visitors_count ?? 0;
        $avgVisitorsPerRoom = Room::whereHas('owner', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })
            ->withCount('roomVisitors')
            ->get()
            ->avg('room_visitors_count');

        $avgMicPerRoom = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
            ->pluck('microphone')
            ->filter()
            ->map(fn($mics) => count(array_filter(explode(',', $mics))))
            ->avg();
        //return back another way
        //        $topMicRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
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
        $roomsWithMic = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
            ->whereNotNull('microphone')
            ->where('microphone', '!=', '')
            ->count();
        $percentageWithMic = $totalRooms > 0 ? ($roomsWithMic / $totalRooms) * 100 : 0;

        //agencies
        $agencyCount = Agency::where('country_id', $countryID)->count();
        $user_salaries = UserSallary::query()
            ->whereHas('user', function ($q) use ($countryID) {
                $q->where('agency_id', '!=', 0)
                    ->where('country_id', $countryID)
                    ->whereHas('agency', fn($a) => $a->where('country_id', $countryID));
            })
            ->sum(DB::raw('sallary - cut_amount'));
        $agency_salaries = AgencySallary::query()->whereHas('agency', function ($q) use ($countryID) {
            $q->where('country_id', $countryID);
        })->sum(DB::raw('sallary - cut_amount'));

        $activeAgencies = Agency::where('country_id', $countryID)
            ->whereHas('agencySalaries', fn($q) => $q->where('month', now()->month)
                ->where('year', now()->year))
            ->count();
        $newAgenciesToday = Agency::where('country_id', $countryID)->whereDate('created_at', today())->count();
        $newAgenciesMonth = Agency::where('country_id', $countryID)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $topAgencies = Agency::where('country_id', $countryID)
            ->orderByDesc('coins')
            ->take(10)
            ->get(['id', 'name', 'coins']);
        $avgAgencyWallet = Agency::where('country_id', $countryID)->avg('coins');
        $totalMembers = User::where('country_id', $countryID)
            ->where('agency_id', '!=', 0)
            ->whereHas('agency', function ($q) use ($countryID) {
                $q->where('country_id', $countryID);
            })
            ->count();
        $avgMembersPerAgency = $agencyCount > 0 ? $totalMembers / $agencyCount : 0;
        $pendingJoins = Agency::where('country_id', $countryID)
            ->whereHas('joinRequests', fn($q) => $q->where('status', 1))
            ->count();
        $diamondsAchieved = UserSallary::whereHas('user', fn($q) => $q->where('country_id', $countryID))
            ->whereHas('agency', function ($q) use ($countryID) {
                $q->where('country_id', $countryID);
            })
            ->sum('achieved_diamond');

        //others
        $bdCount = Bd::where('parent_id', auth()->id())->where('country_id', $countryID)->count();
        $diAuth = Auth::user()->di;
        $totals = Charge::selectRaw("
                SUM(CASE WHEN user_type = ? AND user_id = ? THEN amount ELSE 0 END) as total_charges,
                SUM(CASE WHEN charger_type = ? AND charger_id = ? THEN amount ELSE 0 END) as total_spent
            ", [
            UserTypeEnum::SUPER_ADMIN,
            Auth::user()->id,
            UserTypeEnum::SUPER_ADMIN,
            Auth::user()->id
        ])
            ->first();

        $totalCharges = $totals->total_charges;
        $totalSpent   = $totals->total_spent;

        $totalSalaries = BD::where('parent_id', auth()->id())->where('country_id', $countryID)
            ->withSum('salaries', 'salary')
            ->withSum('salaries', 'cut_amount')
            ->withCount('agencies')
            ->get();

        $totalBDSalary = $totalSalaries->sum('salaries_sum_salary');
        $totalBDCut = $totalSalaries->sum('salaries_sum_cut_amount');
        $averageAgenciesPerBD = $totalSalaries->avg('agencies_count');

        return $content
            ->title(__('Home'))
            ->description(__('General Statistics'))

            ->row(function (Row $row) use ($totalCharges,$totalSpent,$agencyCount, $usersCount, $bdCount, $onlineUser, $diAuth, $roomCounts, $agency_salaries, $user_salaries, $countryID, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration, $liveRooms, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved, $liveRoomsTrue, $liveRoomsFalse,$topUsersByFollowers,$totalBDSalary, $totalBDCut,$averageAgenciesPerBD,$game) {
                $row->column(4, new InfoBox(__('you Wallet'), 'money', 'green', '/', $diAuth . '💎'));
                $row->column(4, new InfoBox(__('total charges'), 'money', 'green', '', truncateAndTrim($totalCharges, 2) . ' 💰'));
                $row->column(4, new InfoBox(__('total spent'), 'money', 'red', 'charges', truncateAndTrim($totalSpent, 2)));
                $row->column(12, function ($column) use ($usersCount, $onlineUser, $countryID, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration, $topUsersByFollowers) {
                    $column->row("<h3 style='margin:10px 0;'>👤 " . __('Users') . "</h3>");

                    $column->row(function (Row $row) use ($countryID, $usersCount, $onlineUser, $peakHours, $totalRoomsJoined, $newSignUpsToday, $newSignUpsThisWeek, $newSignUpsThisMonth, $messagesToday, $messagesThisMonth, $usersWhoSend, $usersWhoNeverSend, $openConversationsToday, $avgConversationDuration) {
                        $row->column(3, new InfoBox(__('Users Count'), 'users', 'aqua', admin_url("users"), $usersCount));
                        $row->column(3, new InfoBox(__('Online Users Count'), 'user', 'blue', admin_url("users?online=1&country_id=$countryID"), $onlineUser));
                        if ($peakHours) {
                            $time = Carbon::createFromTime($peakHours->hour);
                            $time->locale(app()->getLocale());
                            $peakHour = $time->isoFormat('h A');
                            $peakHourCount = $peakHours->total_sessions;
                            $value = $peakHour . ' • ' . $peakHourCount . ' ' . __('Users');
                        } else {
                            $value = 0;
                        }
                        $row->column(3, new InfoBox(__('Peak Hour'), 'clock-o', 'green', admin_url("users"), $value));
                        $row->column(3, new InfoBox(__('New Sign Ups Today'), 'user-plus', 'yellow', admin_url("users?signups=today&country_id=$countryID"), $newSignUpsToday));
                        $row->column(3, new InfoBox(__('New Sign Ups This Week'), 'users', 'red', admin_url("users?signups=week&country_id=$countryID"), $newSignUpsThisWeek));
                        $row->column(3, new InfoBox(__('New Sign Ups This Month'), 'user', 'purple', admin_url("users?signups=month&country_id=$countryID"), $newSignUpsThisMonth));
                        $row->column(3, new InfoBox(__('Messages Today'), 'envelope', 'maroon', admin_url("users?messages=today&country_id=$countryID"), $messagesToday));
                        $row->column(3, new InfoBox(__('Messages This Month'), 'comments', 'teal', admin_url("users?messages=month&country_id=$countryID"), $messagesThisMonth));
                        $row->column(3, new InfoBox(__('Users Who Send Messages'), 'user', 'gray', admin_url("users?sent_messages=1&country_id=$countryID"), $usersWhoSend));
                        $row->column(3, new InfoBox(__('Users Who Never Send'), 'user-times', 'orange', admin_url("users?never_send=1&country_id=$countryID"), $usersWhoNeverSend));
                        $row->column(3, new InfoBox(__('Open Conversations Today'), 'comments-o', 'lime', admin_url("users"), $openConversationsToday));
                        $row->column(3, new InfoBox(__('Avg Conversation Duration (min)'), 'clock-o', 'olive', admin_url("users"), round($avgConversationDuration)));
                    });

                    $column->row(function (Row $row) use ($countryID, $topUsersByFollowers) {
                        // Right: chart view (Top Salaries)
                        $row->column(6, function ($column) use ($countryID) {
                            $topUsersByLiveTime = LiveTime::query()
                                ->whereHas('user', function ($q) use ($countryID) {
                                    $q->where('country_id', $countryID);
                                })
                                ->selectRaw('uid, SUM(hours) as total_hours, COUNT(DISTINCT DATE(created_at)) as active_days')
                                ->groupBy('uid')
                                ->havingRaw('SUM(hours) >= 1')
                                ->orderByDesc('total_hours')
                                ->take(10)
                                ->get();

                            $labels = User::whereIn('id', $topUsersByLiveTime->pluck('uid'))->where('country_id', $countryID)->pluck('name');
                            $data   = $topUsersByLiveTime->pluck('total_hours');

                            $view = view('admin.widgets.users_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });

                        // Left: top 10 salaries
                        $row->column(6, function ($column) use ($countryID, $topUsersByFollowers) {

                            $view = view('admin.widgets.top_users_visits_chart')->render();

                            $column->row($view);
                        });

                        $row->column(6, function ($column) use ($countryID) {
                            $currMonth = now()->month;
                            $prevMonth = now()->subMonth()->month;

                            $signups = User::where('country_id', $countryID)
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

                $row->column(12, function ($column) use ($countryID, $topUsersByFollowers) {
                    $column->row(function (Row $row) use ($countryID, $topUsersByFollowers) {
                        $row->column(6, function ($col) use ($topUsersByFollowers) {
                            $top5 = $topUsersByFollowers
                                ->filter(fn($user) => $user->followers_count > 0)
                                ->take(5);

                            $view5 = view('admin.widgets.top_followers_table', [
                                'top5' => $top5,
                            ])->render();

                            $col->row($view5);
                        });

                        $row->column(6, function ($col) use ($countryID) {
                            $view = view('admin.widgets.users_online_chart')->render();
                            $col->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($countryID, $roomCounts, $totalRoomsJoined, $liveRooms, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic, $liveRoomsTrue, $liveRoomsFalse,) {
                    $column->row("<h3 style='margin:10px 0;'>🏠 " . __('Rooms') . "</h3>");

                    $column->row(function (Row $row) use ($countryID, $roomCounts, $totalRoomsJoined, $liveRoomsTrue, $liveRoomsFalse, $mostVisitedRoomCount, $avgVisitorsPerRoom, $longestActiveRoom, $avgMicPerRoom, $roomsWithMic, $percentageWithMic) {
                        $row->column(3, new InfoBox(__('Audio Rooms'), 'headphones', 'blue', admin_url("rooms?online=1&country_id=$countryID"), $roomCounts['audio'] ?? 0));
                        $row->column(3, new InfoBox(__('Live Rooms'), 'microphone', 'green', admin_url("live-rooms?online=1&country_id=$countryID"), $roomCounts['live'] ?? 0));
                        //                        $row->column(3, new InfoBox(__('Total Rooms Joined By Visitors'), 'building', 'yellow', 'superadmin/rooms', $totalRoomsJoined));
                        //                        $row->column(3, new InfoBox(__('Top Room Messages'), 'commenting', 'teal', 'superadmin/rooms/' . ($topRoom ? $topRoom->id : '#'), $topRoom ? $topRoom->messages_count : 0));
                        $row->column(3, new InfoBox(__('Live Rooms'), 'microphone', 'purple', admin_url("live-rooms?online=1&country_id=$countryID"), $roomCounts['live'] ?? 0));
//                        $row->column(3, new InfoBox(__('Total Rooms Joined By Visitors'), 'building', 'yellow', 'superadmin/rooms', $totalRoomsJoined));
//                        $row->column(3, new InfoBox(__('Top Room Messages'), 'commenting', 'teal', 'superadmin/rooms/' . ($topRoom ? $topRoom->id : '#'), $topRoom ? $topRoom->messages_count : 0));
                        $row->column(3, new InfoBox(__('Live Rooms (Active)'), 'microphone', 'green', admin_url("live-rooms?is_live=1&country_id=$countryID"), $liveRoomsTrue));
                        $row->column(3, new InfoBox(__('Live Rooms (Inactive)'), 'microphone-slash', 'red', admin_url("live-rooms?is_live=0&country_id=$countryID"), $liveRoomsFalse));
                        //                        $row->column(3, new InfoBox(__('Most Visited Room (visitors)'), 'users', 'lime', 'superadmin/rooms', $mostVisitedRoomCount));
                        //                        $row->column(3, new InfoBox(__('Avg Visitors Per Room'), 'user-plus', 'gray', 'superadmin/rooms', round($avgVisitorsPerRoom, 2)));
                        //                        $row->column(3, new InfoBox(__('Longest Active Room (days)'), 'clock-o', 'yellow', 'superadmin/rooms', $longestActiveRoom));
                        //                        $row->column(3, new InfoBox(__('Avg Mic Users per Room'), 'users', 'purple', 'superadmin/rooms', round($avgMicPerRoom, 2)));
                        //                        $row->column(3, new InfoBox(__('Rooms With Mic Usage'), 'volume-up', 'maroon', 'superadmin/rooms', $roomsWithMic));
                        //                        $row->column(3, new InfoBox(__('Rooms With Mic (%)'), 'pie-chart', 'teal', 'superadmin/rooms', round($percentageWithMic, 1) . '%'));
                    });

                    $column->row(function (Row $row) use ($countryID) {
                        //chart 1
                        $row->column(6, function ($column) use ($countryID) {
                            $roomsWithPk = Room::whereHas('owner', function ($q) use ($countryID) {
                                $q->where('country_id', $countryID);
                            })
                                ->has('lastPk')
                                ->count();

                            $audioRooms = Room::whereHas('owner', function ($q) use ($countryID) {
                                $q->where('country_id', $countryID);
                            })
                                ->where('type', 'audio')
                                ->count();

                            $liveRooms = Room::whereHas('owner', function ($q) use ($countryID) {
                                $q->where('country_id', $countryID);
                            })
                                ->where('type', 'live')
                                ->count();

                            $inactiveRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
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
                        $row->column(6, function ($column) use ($countryID) {
                            $topGiftedRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
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
                        $row->column(6, function ($column) use ($countryID) {
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
                            $data   = $avgSessionRooms->pluck('avg_duration');
                            $view = view('admin.widgets.avg_session_duration_chart', [
                                'labels' => $labels,
                                'data'   => $data,
                            ])->render();

                            $column->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($countryID, $agencyCount, $agency_salaries, $user_salaries, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved) {
                    $column->row("<h3 style='margin:10px 0;'>🏢 " . __('Agencies') . "</h3>");

                    $column->row(function (Row $row) use ($countryID, $agencyCount, $agency_salaries, $user_salaries, $activeAgencies, $newAgenciesToday, $newAgenciesMonth, $topAgencies, $avgAgencyWallet, $totalMembers, $avgMembersPerAgency, $pendingJoins, $diamondsAchieved) {
                        $row->column(3, new InfoBox(__('Agencies Count'), 'building', 'olive', admin_url("agencies"), $agencyCount));
                        $row->column(3, new InfoBox(__('total agency salary'), 'building', 'lime', admin_url("agencies"), round($agency_salaries)));
                        $row->column(3, new InfoBox(__('Total Users Salary'), 'money', 'gray', admin_url("ag/users"), round($user_salaries)));
                        $row->column(3, new InfoBox(__('Active Agencies'), 'building', 'red', admin_url("agencies?active=true&country_id=$countryID"), $activeAgencies));
                        $row->column(3, new InfoBox(__('New Agencies Today'), 'plus', 'teal', admin_url("agencies?created=today&country_id=$countryID"), $newAgenciesToday));
                        $row->column(3, new InfoBox(__('New Agencies This Month'), 'calendar', 'orange', admin_url("agencies?created=month&country_id=$countryID"), $newAgenciesMonth));
                        $row->column(3, new InfoBox(__('Average Agency Wallet'), 'money', 'aqua', admin_url("agencies"), round($avgAgencyWallet)));
                        $row->column(3, new InfoBox(__('Total Members in Agencies'), 'users', 'maroon', admin_url("users?agencyMembers=1&country_id=$countryID"), $totalMembers));
                        $row->column(3, new InfoBox(__('Avg Members Per Agency'), 'user', 'lime', admin_url("agencies"), round($avgMembersPerAgency)));
                        $row->column(3, new InfoBox(__('Pending Join Requests'), 'hourglass', 'purple', admin_url("agencies?pending=1&country_id=$countryID"), $pendingJoins));
                        $row->column(3, new InfoBox(__('Diamonds Achieved by Hosts'), 'diamond', 'green', admin_url("ag/users")   , $diamondsAchieved));
                    });

                    $column->row(function (Row $row) use ($countryID) {
                        //chart 1
                        $row->column(6, function ($column) use ($countryID) {
                            $topAgenciesByTargets = UserTarget::whereHas('agency', fn($q) => $q->where('country_id', $countryID))
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
                        $row->column(6, function ($column) use ($countryID) {
                            $topSenders = GiftLog::whereHas(
                                'sender',
                                fn($q) =>
                                $q->where('country_id', $countryID)
                                    ->whereHas('agency', fn($a) => $a->where('country_id', $countryID))
                            )
                                ->selectRaw('sender_id, SUM(giftPrice * giftNum) as total_sent')
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
                        $row->column(6, function ($column) use ($countryID) {
                            $topReceivers = GiftLog::whereHas(
                                'receiver',
                                fn($q) =>
                                $q->where('country_id', $countryID)
                                    ->whereHas('agency', fn($a) => $a->where('country_id', $countryID))
                            )
                                ->selectRaw('receiver_id, SUM(giftPrice * giftNum) as total_received')
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
                        $row->column(6, function ($column) use ($countryID) {
                            $achievedAgencies = Agency::where('country_id', $countryID)
                                ->whereHas('userTarget', function ($q) {
                                    $q
                                        //                                        ->where('add_month', now()->month)
                                        //                                        ->where('add_year', now()->year)
                                        ->where('agency_obtain', '>', 0);
                                })
                                ->count();

                            $notAchievedAgencies = Agency::where('country_id', $countryID)
                                ->count() - $achievedAgencies;

                            $view = view('admin.widgets.agencies_compare_chart', [
                                'achieved'    => $achievedAgencies,
                                'notAchieved' => $notAchievedAgencies,
                            ])->render();

                            $column->row($view);
                        });
                    });
                });
                $row->column(12, function ($column) use ($countryID, $bdCount, $totalBDSalary, $totalBDCut, $averageAgenciesPerBD) {
                    $column->row("<h3 style='margin:10px 0;'>💼 " . __('BD') . "</h3>");

                    $column->row(function (Row $row) use ($countryID, $bdCount, $totalBDSalary, $totalBDCut, $averageAgenciesPerBD) {
                        $row->column(3, new InfoBox(__('Bd Count'), 'briefcase', 'aqua', admin_url("usersBD"), $bdCount));
                        $row->column(3, new InfoBox(__('Total BD Salary'), 'wallet', 'green', admin_url("bd-salaries"), number_format($totalBDSalary)));
                        $row->column(3, new InfoBox(__('Total Cut Amount'), 'money-bill-wave', 'red', admin_url("bd-salaries"), number_format($totalBDCut)));
                        $row->column(3, new InfoBox(__('Average Agencies Per BD'), 'briefcase', 'aqua', admin_url("bd-salaries"), number_format($averageAgenciesPerBD)));
                    });
                });

                $row->column(12, function ($column) use ($game) {
                    $column->row("<h3 style='margin:10px 0;'>💼 " . __('game') . "</h3>");

                    $column->row(function (Row $row) use ($game) {
                        $row->column(3, new InfoBox(__('Total Played'), 'gamepad', 'blue',"" ,number_format($game->total_played ?? 0, 2)));
//                        $row->column(3, new InfoBox(__('Total Loss'), 'times-circle', 'red',"", number_format($game->total_loss ?? 0, 2)));
//                        $row->column(3, new InfoBox(__('Total Win'), 'trophy', 'orange',"", number_format($game->total_win ?? 0, 2)));
//                        $row->column(3, new InfoBox(__('App Profit'), 'dollar', 'green',"", number_format($game->app_profit ?? 0, 2)));
                    });
                });
            });
    }


    public function peakHours(Request $request)
    {
        $countryID = Auth::user()->country_id;
        $period = $request->get('period', 'day');

        $query = DB::table('live_times')
            ->join('users', 'live_times.uid', '=', 'users.id')
            ->where('users.country_id', $countryID);

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

    public function onlineStats()
    {
        $countryID = Auth::user()->country_id;
        $online  = User::where('country_id', $countryID)->where('online', 1)->count();
        $offline = User::where('country_id', $countryID)->where('online', 0)->count();


        return response()->json(data: [
            'online'  => $online,
            'offline' => $offline,
        ]);
    }
    public function roomsActivity(Request $request)
    {
        $period = $request->get('period', 'day');
        $countryID = Auth::user()->country_id;

        if ($period === 'day') {
            // last 7 days
            $dates = collect(range(0, 6))
                ->map(fn($i) => now()->subDays($i)->format('Y-m-d'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $newRooms = $dates->map(fn($d) => $newRoomsData[$d] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->where('users.country_id', $countryID)
                ->whereDate('live_times.created_at', '>=', now()->subDays(6))
                ->selectRaw('DATE(live_times.created_at) as date, COUNT(DISTINCT users.id) as active_owners')
                ->groupBy('date')
                ->pluck('active_owners', 'date');

            $totalRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))->count();

            $inactiveRooms = $dates->map(fn($d) => $totalRooms - ($activeOwners[$d] ?? 0));

            $labels = $dates;
        } elseif ($period === 'week') {
            // last 4 weeks
            $weeks = collect(range(0, 3))
                ->map(fn($i) => now()->subWeeks($i)->format('o-\WW'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
                ->where('created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(created_at) as year, WEEK(created_at,1) as week, COUNT(*) as total")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->total]);

            $newRooms = $weeks->map(fn($w) => $newRoomsData[$w] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->where('users.country_id', $countryID)
                ->whereDate('live_times.created_at', '>=', now()->subWeeks(3)->startOfWeek())
                ->selectRaw("YEAR(live_times.created_at) as year, WEEK(live_times.created_at,1) as week, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('year', 'week')
                ->get()
                ->mapWithKeys(fn($r) => [sprintf('%d-W%02d', $r->year, $r->week) => $r->active_owners]);

            $totalRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))->count();

            $inactiveRooms = $weeks->map(fn($w) => $totalRooms - ($activeOwners[$w] ?? 0));

            $labels = $weeks;
        } elseif ($period === 'month') {
            // last 6 months
            $months = collect(range(0, 5))
                ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
                ->reverse()
                ->values();

            $newRoomsData = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))
                ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym');

            $newRooms = $months->map(fn($m) => $newRoomsData[$m] ?? 0);

            $activeOwners = \DB::table('users')
                ->join('live_times', 'users.id', '=', 'live_times.uid')
                ->where('users.country_id', $countryID)
                ->where('live_times.created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw("DATE_FORMAT(live_times.created_at,'%Y-%m') as ym, COUNT(DISTINCT users.id) as active_owners")
                ->groupBy('ym')
                ->pluck('active_owners', 'ym');

            $totalRooms = Room::whereHas('owner', fn($q) => $q->where('country_id', $countryID))->count();

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


    public function topUsersVisits(Request $request)
    {
        $countryID = Auth::user()->country_id;

        // Performance fix: replaced correlated subquery (withCount) with JOIN
        // Old query caused full table scan on live_times (74K rows) per user → 1+ hour stuck queries
        $topUsers = User::select('users.id', 'users.name', DB::raw('SUM(live_times.hours) as total_hours'))
            ->join('live_times', 'users.id', '=', 'live_times.uid')
            ->where('users.country_id', $countryID)
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
}
