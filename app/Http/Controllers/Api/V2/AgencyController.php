<?php

namespace App\Http\Controllers\Api\V2;


use App\Http\Resources\JoinedAgencyResource;
use Cache;
use App\Models\User;
use App\Models\Agency;
use App\Helpers\Common;
use App\Models\Setting;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use App\Models\AgencyUserJob;
use PHPUnit\Framework\Exception;
use App\Models\AgencyJoinRequest;
use App\Tik\Services\AgencyService;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Api\V1\AdminsAgencyResource;
use App\Http\Resources\Api\V1\AgencyDetailsResource;
use App\Http\Resources\Api\V1\AgencyJoinReqResource;
use App\Http\Resources\Api\V1\AllDataAgencyResource;
use App\Http\Resources\Api\V1\HistoryAgencyResource;
use App\Http\Resources\Api\V1\SenderGiftLogResource;
use App\Http\Resources\Api\V1\MyDataForAgancyResource;
use App\Http\Resources\Api\V1\ReceiverGiftLogResource;
use App\Http\Resources\Api\V1\MyDataForAgencyNewResource;
use App\Models\GiftLog;
use App\Models\Target;
use App\Models\UserSallary;
use Carbon\Carbon;

class AgencyController extends Controller
{
    protected $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function joinRequest(Request $request)
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            throw new Exception(__('Agency Feature is Disabled, Contact the administration'));
        }

        $user   = $request->user();
        if ($user->is_bd) return Common::apiResponse(false, 'You are BD, You can\'t join agency', null, 407);

        if (!$request->agency_id) return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);

        try {
            $requests = $this->agencyService->joinAgency($user, $request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, __('api_responses.request_sent'), AgencyJoinReqResource::collection($requests));
    }

    public function view(Request $request)
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            throw new Exception(__('Agency Feature is Disabled, Contact the administration'));
        }
        $agencyId = request()->get('id', $request->user()->agency_id);

        try {
            $agency = $this->agencyService->find($agencyId);

            $year  = request('year')  ?? Carbon::now()->year;
            $month = request('month') ?? Carbon::now()->month;

            $giftData = Cache::remember("agency_gifts_{$agencyId}_{$year}_{$month}", 600, function () use ($agencyId, $year, $month) {
                return $this->computeAgencyGiftData($agencyId, $year, $month);
            });
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, '', new AllDataAgencyResource($agency, $giftData));
    }

    public function agencyDetails($id)
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            return Common::apiResponse(0, __('Agency Feature is Disabled, Contact the administration'), null, 400);
        }

        try {
            $year  = request('year')  ?? Carbon::now()->year;
            $month = request('month') ?? Carbon::now()->month;

            // Cache the agency model (relations only, no gift aggregates)
            $agency = Cache::remember("agency_details_{$id}_{$year}_{$month}", 600, function () use ($id) {
                return $this->agencyService->find($id);
            });

            // Cache gift aggregations separately so they are always current
            $giftData = Cache::remember("agency_gifts_{$id}_{$year}_{$month}", 600, function () use ($id, $year, $month) {
                return $this->computeAgencyDetailsGiftData($id, $year, $month);
            });
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, '', new AgencyDetailsResource($agency, $giftData));
    }

    public function history($id, Request $request)
    {
        $user = $request->user();
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            // throw new Exception(__('Agency Feature is Disabled, Contact the administration'));

            return Common::apiResponse(0, __('Agency Feature is Disabled, Contact the administration'), null, 400);
        }

        try {
            // Cache for 10 minutes (600 seconds) as recommended by DevOps report
            // This fixes the 0.3s-6.5s latency issue for agencies/history/{id}
            // Cache key includes year/month since history changes monthly
            $year = request('year') ?? \Carbon\Carbon::now()->year;
            $month = request('month') ?? \Carbon\Carbon::now()->month;
            $cacheKey = "agency_history_{$id}_{$year}_{$month}";

            $agency = Cache::remember($cacheKey, 600, function () use ($id) {
                return $this->agencyService->find($id);
            });

            request()->is_onwer_agency = ($user->id !=  $agency->app_owner_id);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '', new HistoryAgencyResource($agency));
    }

    public function admin($id)
    {
        try {
            $agency = $this->agencyService->find($id);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '', AdminsAgencyResource::collection($agency->admins));
    }

    public function agencyTargetDetails($agencyId, Request $request)
    {
        $user = $request->user();
        try {
            // Cache for 10 minutes (600 seconds) as recommended by DevOps report
            // This fixes the 1.0s-5.3s latency issue for agencies/target-details/{id}
            // Cache key includes year/month since target changes monthly
            $year = request('year') ?? \Carbon\Carbon::now()->year;
            $month = request('month') ?? \Carbon\Carbon::now()->month;
            $cacheKey = "agency_target_{$user->agency_id}_{$year}_{$month}";

            $response = Cache::remember($cacheKey, 600, function () use ($user, $request) {
                return $this->agencyService->agencyTarget($user->agency_id, $user, $request);
            });
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(
            1,
            $response['message'],
            $response['data'],
            $response['status'],
            '',
            'users_target'
        );
    }

    public function star($id, Request $request)
    {
        try {
            $data = $this->agencyService->stars($id, $request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '',  ReceiverGiftLogResource::collection($data), 200);
    }

    public function heroes($id, Request $request)
    {
        try {
            $data = $this->agencyService->heroes($id, $request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '',  SenderGiftLogResource::collection($data), 200);
    }

    public function agencyMembers(Request $request)
    {
        $agencyId  = $request->user()->agency_id;
        $page      = (int) $request->get('page', 1);
        $perPage   = (int) $request->get('per_page', 20);
        try {
            $members = $this->agencyService->agencyMembers($agencyId, $page, $perPage);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, '', MyDataForAgancyResource::collection($members), 200, Common::getPaginates($members));
    }

    public function show_request(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $requestList = $this->agencyService->showRequests($userId);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '', MyDataForAgancyResource::collection($requestList));
    }

    public function Accept_request(Request $request)
    {
        $accept    = $request->accept;
        $owner     = $request->user();
        if (!$request->user_id || !isset($request->accept)) {
            return Common::apiResponse(0, 'missing params');
        }
        try {
            $this->agencyService->requestAction($owner, $request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        if ($accept === 0 || $accept === false) {

            return Common::apiResponse(1, 'joinfalse');
        } elseif ($accept === 1 || $accept === true) {
            return Common::apiResponse(1, 'joinSacsesAg');
        }
    }

    public function list_options_his(Request $request)
    {
        $agencyId      = $request->user()->agency_id;
        // Add the current month and year
        $monthsToInclude = $this->agencyService->listOption($agencyId);
        return Common::apiResponse(1, '', $monthsToInclude);
    }

    public function historyAgencySearch(Request $request)
    {
        $agencyId         = $request->user()->agency_id;
        $responseData = $this->agencyService->historySearch($agencyId, $request);
        return Common::apiResponse(1, '', $responseData);
    }

    public function update(Request $request, $id)
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            throw new Exception(__('Agency Feature is Disabled, Contact the administration'));
        }

        $userId = $request->user()->id;

        try {
            $agency = $this->agencyService->update($userId, $id, $request);

            // Clear agency cache after update (current month + gift aggregations)
            $year  = Carbon::now()->year;
            $month = Carbon::now()->month;
            Cache::forget("agency_details_{$id}_{$year}_{$month}");
            Cache::forget("agency_history_{$id}_{$year}_{$month}");
            Cache::forget("agency_target_{$id}_{$year}_{$month}");
            Cache::forget("agency_gifts_{$id}_{$year}_{$month}");
        } catch (\Exception $e) {

            return Common::apiResponse(0, $e->getMessage(), null, 500);
        }

        return Common::apiResponse(1, __('api_responses.agency_updated'), new AllDataAgencyResource($agency));
    }

    public function make_user_handling_requests(Request $request)
    {
        $user = $request->user();
        $type = $request->type ?? null;
        $agency = $user->ownAgency;
        if (!$user->ownAgency)   return Common::apiResponse(0, 'لا يوجد وكاله!', []);

        //        try {
        $mass =  $this->agencyService->userHandlingRequest($request->user_id, $agency->id, $type);
        /*  } catch (ValidationException $exception){
            return Common::apiResponse(0, $exception->getMessage(), null, 422);
        } catch (\Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 500);
        }*/

        return Common::apiResponse(1, $mass, []);
    }



    public function showAgencyRequest(Request $request)
    {
        $user   = $request->user();
        $type = $request->type;

        $admin = AgencyUserJob::where('user_id', $user->id)->where('type', 'requestManger')->first();
        if ($admin) {
            $agency = Agency::where('id', $admin->agency_id)->first();
        } else {
            $agency = Agency::where('app_owner_id', $user->id)->first();
        }

        if (!$agency) {
            return Common::apiResponse(0, __('api_responses.notAdmin'));
        }

        $agency_id = $agency->id;
        $list_req = AgencyJoinRequest::where('agency_id', $agency_id)->whereHas('user')->orderByDesc('id');

        if ($type == "application") {
            $list_req1 = $list_req->where('status', 0)->with(['user.profile', 'user.country'])->paginate(10);
            $list_req = MyDataForAgencyNewResource::collection($list_req1, 'application');
        } elseif ($type == "record") {
            $list_req1 = $list_req->where('status', '!=', 0)->with(['user.profile', 'user.country', 'admin'])->paginate(10);
            $list_req = MyDataForAgencyNewResource::collection($list_req1, 'record');
        }

        if ($list_req) {
            return Common::apiResponse(1, '', $list_req, 200);
        }
        return Common::apiResponse(0, 'لا يوجد بيانات', []);
    }

    public function agenciesCharge(Request $request)
    {

        try {
            $agencies = $this->agencyService->allAgencyCharged($request->agency_id);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '',  AllDataAgencyResource::collection($agencies));
    }

    // public function showAgencyRequest(Request $request)
    // {
    //     $user = $request->user();
    //     $type = $request->type;

    //     // استدعاء الخدمة لمعالجة الطلب
    //     $data = $this->agencyService->showAgencyRequest($user, $type);
    //     return Common::apiResponse(1, '',$data);
    // }




    public function gitOldAgencies(Request $request)
    {
        $userId   = $request->user()->id;
        try {
            $agency = $this->agencyService->gitOldAgencies($userId);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '', JoinedAgencyResource::collection($agency));
    }

    // -------------------------------------------------------------------------
    // Private helpers: compute gift aggregations in the controller so they can
    // be cached independently and are never executed inside JsonResource::toArray
    // -------------------------------------------------------------------------

    /**
     * Gift data for AgencyDetailsResource (agencyDetails endpoint).
     *
     * Stars: top-3 receivers in the last 30 days (fix1 — was all-time, no date filter).
     * Heroes: top senders for the requested month/year.
     * Admins: first 3 admins (eager relation, no extra query needed via get()).
     */
    private function computeAgencyDetailsGiftData(int $agencyId, int $year, int $month): array
    {
        $cutoff = Carbon::now()->subDays(30)->startOfDay();

        $stars = GiftLog::where('agency_id', $agencyId)
            ->selectRaw('SUM(giftPrice) as exp, receiver_id')
            ->with('receiver')
            ->groupBy('receiver_id')
            ->whereHas('receiver')
            ->where('created_at', '>=', $cutoff)        // last-30-days filter (fix1)
            ->orderByDesc('exp')
            ->take(3)
            ->get();

        $heroes = GiftLog::where('agency_id', $agencyId)
            ->selectRaw('SUM(giftPrice) as exp, sender_id')
            ->with('sender')
            ->groupBy('sender_id')
            ->whereHas('sender')
            ->whereBetween('created_at', [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ])
            ->orderByDesc('exp')
            ->get();

        // Load admins here (3 rows) so the resource never touches the DB
        $admins = \App\Models\AgencyUserJob::where('agency_id', $agencyId)
            ->with('user.profile')
            ->take(3)
            ->get();

        return compact('stars', 'heroes', 'admins');
    }

    /**
     * Gift data for AllDataAgencyResource (view endpoint).
     *
     * Includes target calculation (UserSallary + Target) and top-5 gift logs
     * for receivers/senders within the requested month/year (last-30-days window).
     */
    private function computeAgencyGiftData(int $agencyId, int $year, int $month): array
    {
        $cutoff = Carbon::now()->subDays(30)->startOfDay();

        // Target calculation (moved from resource — fix2)
        $salaryTotal = UserSallary::where('user_agency_id', $agencyId)->sum('agency_sallary');
        $minValue    = Target::where('usd', '<', $salaryTotal)->orderBy('usd', 'desc')->first();
        $target      = ($minValue ? ($minValue->agency_share / 100) * $salaryTotal : 0);

        $stars = GiftLog::where('agency_id', $agencyId)
            ->selectRaw('SUM(giftPrice) as exp, receiver_id')
            ->with('receiver')
            ->groupBy('receiver_id')
            ->whereHas('receiver')
            ->where('created_at', '>=', $cutoff)        // last-30-days filter (fix1)
            ->orderByDesc('exp')
            ->take(5)
            ->get();

        $heroes = GiftLog::where('agency_id', $agencyId)
            ->selectRaw('SUM(giftPrice) as exp, sender_id')
            ->with('sender')
            ->groupBy('sender_id')
            ->whereHas('sender')
            ->where('created_at', '>=', $cutoff)        // last-30-days filter (fix1)
            ->orderByDesc('exp')
            ->take(5)
            ->get();

        return compact('target', 'stars', 'heroes');
    }
}
