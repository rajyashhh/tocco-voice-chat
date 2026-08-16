<?php

namespace Modules\AgencyApp\Http\Controllers\Api;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Http\Resources\Api\V1\AgencyResource;
use App\Models\Agency;
use App\Models\Follow;
use App\Models\GiftLog;
use App\Models\LiveTime;
use App\Models\ProfileVisitor;
use App\Models\User;
use App\Models\UserSallary;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\AgencyApp\Classes\Agencies\AgencyDataSearch;
use Modules\AgencyApp\Entities\AdditionalInfo;
use Modules\AgencyApp\Entities\AgencyUserJob;
use Modules\AgencyApp\Entities\LeaveAgencyRequest;
use Modules\AgencyApp\Services\TargetService;
use Modules\FixedTarget\Services\FixedTargetService;
use Modules\SalaryTransaction\Transformers\FilterAgancyResource;
use Modules\SalaryTransaction\Transformers\FilterAgencyMangerResource;

class AgencyAppController extends Controller
{
    public function agency_last_thirty_day()
    {
        $user_uuid = \request('uuid');
        if(!$user_uuid) return Common::apiResponse(0, 'missing parameters', []);
        $user = User::query()->searchByUuid($user_uuid)->first();
        $endDate = now();
        $startDate = now()->subDays(30);
        $data=[];

        $total_days=0;
        $total_hours=0;
        $total_diamonds=0;

        $days=LiveTime::query()
            ->where('uid', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('uid',\DB::raw('DATE(created_at)'))
            ->havingRaw('SUM(hours) > 1')
            ->selectRaw('uid, SUM(hours) AS hnum, COUNT(DISTINCT DATE(created_at)) as days');
        $hours= LiveTime::query()->where('uid', $user->id)->whereBetween('created_at', [$startDate, $endDate]);
        $diamonds=GiftLog::query()->whereBetween('created_at', [$startDate, $endDate])->where("receiver_id",$user->id);
        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $day        = $days->whereDate('created_at',$date->toDateString())->sum("days") ?? 0;
            $hour       = $hours->whereDate('created_at',$date->toDateString())->sum("hours");
            $diamond    = $diamonds->whereDate('created_at',$date->toDateString())->sum("giftPrice");

            $total_days += $day;
            $total_hours += $hour;
            $total_diamonds += $diamond;
            $data[]=[
                'date'      =>  $day,
                'days'      =>  $day,
                'hours'     =>  $hour,
                'diamonds'  =>  $diamond,
            ];
        }
        $data[]=[
            'total_days'    =>$total_days,
            'total_hours'   =>$total_hours,
            'total_diamonds'=>$total_diamonds,
        ];

        return Common::apiResponse(1, '', $data);
    }

    public function agency_total_reports()
    {
        $agency_id  = \request('agency_id');
        if(!$agency_id) return Common::apiResponse(0, 'missing parameters', []);

        $users      = User::query()->where('agency_id',$agency_id)->pluck("id")->toArray();
        $diamonds   = GiftLog::query()->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->whereIn("receiver_id",$users)->sum('giftPrice');
        $days       =  LiveTime::query()
                                ->whereIn('uid', $users)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->groupBy('uid')
                                ->selectRaw('uid, SUM(hours) AS hnum, COUNT(DISTINCT DATE(created_at)) as days')
                                ->havingRaw('SUM(hours) >= 1')
                                ->get()
                                ->sum('days') ?? 0;
        $hours       = LiveTime::query()->where('uid', $users)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->sum('hours');
        $visitors   = ProfileVisitor::query()->whereIn('user_id',$users)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $follows    = Follow::query()->where(fn($q)=>$q->whereIn("followed_user_id",$users)
                                ->orWhere(fn($q2)=>$q2->whereIn("user_id",$users)->where("status",1)))
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $friends    =   Follow::query()->where(fn($q)=>$q->whereIn("followed_user_id",$users)->orWhereIn("user_id",$users))
                                ->where("status",1)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)->count();
        $data=[
          'diamonds'    =>  $diamonds,
          'hours'       =>  $hours,
          'days'        =>  $days,
          'visitors'    =>  $visitors,
          'friends'     =>  $friends,
          'follows'     =>  $follows,
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function cancel_request_createAgency(Request $request)
    {
        $user = $request->user();
        if ($request->agency_id) {
            $agency =Agency::find($request->agency_id);
        }else{
            $agency = Agency::where('app_owner_id',$user->id)->latest()->first();
        }
        if (!$agency) return Common::apiResponse(0, __('not found request'), []);

        if ($agency->status != 0 && $agency->status != 3 ) return Common::apiResponse(0, __("not cancel request"), []);
        if ($agency->status == 3 ) return Common::apiResponse(0, __('you already canceled'), []);

        $time_after24=Carbon::parse($agency->created_at)->addHours(24);
        if ($agency->created_at > $time_after24) return Common::apiResponse(0, __("24 hours have passed since your request"), []);

        $agency ->delete();
        AdditionalInfo::where('agency_id',$agency->id)->update(['status' => 3]);
        return Common::apiResponse(1, __("The request has been successfully cancelled"), []);
    }

    public function agency_request_info(Request $request)
    {
        $user = Auth::user();
        $additional = AdditionalInfo::where('owner_id',$user->id)->latest()->first();
        $can_make_request = true;
        $can_cancel_request = false;

        if ($additional) {
            $time_after24=Carbon::parse($additional->created_at)->addHours(24);
            if ($additional->status == 0 || $additional->status == 1 ) {
                $can_make_request = false;
             }

            if ($additional->status == 0 &&  $additional->created_at <= $time_after24) {
                $can_cancel_request = true;
             }
        }
        $chekAgency =Agency::where("app_owner_id",$user->id)->first();
        if ($chekAgency) {
            $can_make_request = false;
        }
        $data=[
            'can_make_request' => $can_make_request,
            'can_cancel_request' => $can_cancel_request,
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function user_agency_information()
    {
        $user   =   Auth::user();
        $month  =   \request('month');
        $year  =   \request('year');

        $agency = Agency::query()->where('app_owner_id', $user->id)->first();
        if (!$agency) return Common::apiResponse(0, __("api_responses.u_not_owner_agncy"), []);
        $total_host_target = UserSallary::where('user_agency_id', $agency->id);

        if ($month != null && $year != null) {
            $total_host_target = $total_host_target->where('month', $month)
                ->where('year', $year);
        }
        $total_host_target = $total_host_target->sum('sallary');

        $data = [
            'id'                => $agency->id,
            'name'              => $agency->name,
            'image'             => $agency->img,
            'pio'               => $agency->contents,
            'num_of_hosts'      => $agency->mempers->count(),
            'total_salary'      => $total_host_target,
            'agency_target'     => $agency->getSalary($month, $year),
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function kick_of_agency(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (Carbon::now()->day < 5 || Carbon::now()->day > 10 ) return Common::apiResponse(0, __("api.kickAgency"), []);
        if (!$request->user_id) return Common::apiResponse(0, 'missing_parameters', 404);
        $user_kicked = User::find($request->user_id);

        if ($user_kicked->agency_id != $user->ownAgency->id || $user_kicked->id == $user->ownAgency->app_owner_id) return Common::apiResponse(0, 'لا يمكنك ازاله هذا المستخدم!', []);
        UserHandling::kickUserFromAgency($user_kicked, 1);
        return Common::apiResponse(1, 'تم حذف المستخدم بنجاح', []);
    }

    public function make_user_handling_requests(Request $request)
    {
        $user = $request->user();
        $agency = $user->ownAgency;
        if (!$user->ownAgency)   return Common::apiResponse(0, 'لا يوجد وكاله!', []);
        $operator = User::find($request->user_id);
        if ($agency->id != $operator->agency_id) return Common::apiResponse(0, 'يجب ان يكون المستخدم في الوكاله!', []);
        AgencyUserJob::updateOrCreate([
            'agency_id' => $agency->id,
            'user_id' => $operator->id,
            'type' => "requestManger",
        ]);
        return Common::apiResponse(1, 'تم اضافه المستخدم بنجاح', []);
    }

    public function historyDataAgency(Request $request)
    {
        $data = (new AgencyDataSearch())->fetchData($request);
        return Common::apiResponse(1, '', $data);
    }

    public function leave_agency(Request $request)
    {
        $user = $request->user();
        if (!$user->agency) return Common::apiResponse(0, __("api_responses.agency"), []);
        $agency = $user->agency;
        if (date("d") >= 10) return Common::apiResponse(0, __('api_responses.leave_agency'), []);
        $check = LeaveAgencyRequest::query()->where(['agency_id' =>  $agency->id, 'user_id'   =>  $user->id, 'status'    =>  0])->first();
        if ($check) {
            return Common::apiResponse(0, "هناك طلب من قبل !", []);
        }
        LeaveAgencyRequest::create([
            'agency_id' =>  $agency->id,
            'user_id'   =>  $user->id,
            'admin_id'  =>  $agency->owner_id,
            'status'    =>  0,
        ]);
        return Common::apiResponse(0, __("api_responses.request_sent"), []);
    }

    public function agency_filter(Request $request)
    {
        $keyword = trim((string) $request->keyword);
        $perPage = (int) ($request->per_page ?? 15);
        $perPage = max(1, min($perPage, 30));

        // "Most active this month" is read from the denormalized agencies.monthly_activity
        // column (sum of gifts credited to the agency for the current calendar month),
        // maintained by the agency:update-monthly-activity scheduled command. Ordering by
        // a stored, indexed column keeps this listing cheap instead of recomputing a
        // correlated subquery over gift_logs on every request.
        $query = Agency::query()
            ->available()                 // approved (or no approval record) + not soft-deleted + type=1
            ->where('is_frozen', false)   // hide frozen/suspended agencies
            ->with([
                // Owner (+ its packs/ware) is needed to render the owner avatar
                // frame in the list without an N+1 per row.
                'owner' => fn ($q) => $q->with('packs.ware'),
                'mempers',
                'admins',
                'joinRequests',
                'giftLogs.receiver',
            ])
            ->select('agencies.*')
            ->withCount('mempers');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhereHas('owner', function ($query) use ($keyword) {
                        $query->where('uuid', 'like', '%' . $keyword . '%');
                    });
            });
        }

        // Most active this month first, then biggest agencies, then newest as a stable tiebreaker.
        $agencies = $query
            ->orderByDesc('agencies.monthly_activity')
            ->orderByDesc('mempers_count')
            ->orderByDesc('agencies.id')
            ->paginate($perPage);

        $agency_manger = User::query()->select('*')->selectRaw("((LENGTH(users.uuid) - LENGTH(REPLACE(users.uuid, ?, ''))) / CHAR_LENGTH(users.uuid)) * 100 AS matching_percentage", [$keyword])->has('ownAgency')->where(function ($q) use ($keyword) {
            $q->where('uuid', 'like', '%' . $keyword . '%')
                ->orWhereHas('ownAgency', function ($query) use ($keyword) {
                    $query->where('id', 'like', '%' . $keyword . '%');
                });
        })->orderBy('matching_percentage', 'desc')->take(10)->get();

        $data = [
            'agencies' => FilterAgancyResource::collection($agencies),
            'agency_masters' => FilterAgencyMangerResource::collection($agency_manger),
        ];

        // 'agencies' is a resource collection wrapping the paginator; passing its
        // key as $paginationKey makes apiResponse emit paginates.meta.last_page
        // for the client's infinite-scroll while keeping the transformed rows.
        return Common::apiResponse(1, '', $data, null, null, 'agencies');
    }

    public function dailyReport()
    {
        $user  = \Auth::user();
        $month = request()->month ? (int) request()->month : now()->month;
        $year = request()->year ? (int) request()->year : now()->year;

        if (!$user instanceof User) return;
        $userId        = $user->id;

        $cacheKey = 'cache-data-my-store-' . $user->id;
        if (Cache::add($cacheKey, true, now()->addSeconds(30)))  {
            $targetService = new FixedTargetService($user);
            ($targetService)->calculateTarget();
        }


        $dailyDiamonds = GiftLog::query()
            ->selectRaw('sum(giftPrice) as diamonds, max(created_at) as date')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('receiver_id', $userId)
            ->where('agency_id', $user->agency_id)
            ->groupBy(\DB::raw('date(created_at)'))
            ->limit(31)
            ->get();

        $dailyTimes = LiveTime::query()
            ->selectRaw('sum(hours) as hours, max(created_at) as date')
            ->where('uid', $userId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy(\DB::raw('date(created_at)'))
            ->limit(31)
            ->get();

        $dailyDiamonds = $dailyDiamonds->map(function ($data) {
            $data->day = Carbon::parse($data->date)->day;
            return $data;
        });
        $dailyTimes = $dailyTimes->map(function ($data) {
            $data->day = Carbon::parse($data->date)->day;
            return $data;
        });
        $totalDays = $user->getTotalDays();

        $userInfoArray = $user->getSallaryInfo();

        $totalSalary = @$userInfoArray['total_salary'] ?? 0;
        $totalCutAmount = @$userInfoArray['total_cut_amount'] ?? 0;

        $isThisMonth = $month == now()->month && $year == now()->year;
        $startDay = 1;
        $endDay = Carbon::create($year, $month)->endOfMonth()->day;

        if ($isThisMonth) $endDay = today()->day;

        $hours = $dailyTimes->sum('hours');
        $minutes = $hours * 60;
        $data = [
            'user_salary' => [
                'cut_amount' => $totalCutAmount,
                'salary' => $totalSalary
            ],
            'request_leave_agency' => LeaveAgencyRequest::query()->where(['user_id' => $userId, 'agency_id' => $user->agency_id])->select('id', 'status')->first(),
            'diamonds' => numToStringNew($dailyDiamonds->sum('diamonds')),
            'live_minutes' => (int)$minutes,
            'active_days' => $totalDays,
            'daly_reports' => []
        ];
        for ($startDay; $startDay <= $endDay; $startDay++) {
            $hours = $dailyTimes->where('day', $startDay)->first()?->hours ?? 0;
            $minutes = $hours * 60;
            $diamonds = $dailyDiamonds->where('day', $startDay)->first()?->diamonds ?? 0;
            $data['daly_reports'][] = [
                'day' => $startDay,
                'live_minutes' => (int)$minutes,
                'diamonds' => numToString((int)$diamonds),
                'is_active_day' => $hours >= 1,
            ];
        }


        return Common::apiResponse(true, 'success', $data);
    }

}
