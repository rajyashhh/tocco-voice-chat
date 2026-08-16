<?php

namespace Modules\AgencyApp\Http\Controllers\Api\V2;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use App\Models\GiftLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Tik\Services\AgencyService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Modules\AgencyApp\Emails\SendAgencyEmail;
use Modules\AgencyApp\Services\TargetService;
use Modules\AgencyApp\Classes\Agencies\AgencyDataSearch;
use Modules\FixedTarget\Services\FixedTargetService;
use Modules\SalaryTransaction\Transformers\FilterAgancyResource;
use Modules\SalaryTransaction\Transformers\FilterAgencyMangerResource;

class AgencyAppController extends Controller
{
    protected $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function createAgency(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'img' => 'nullable|mimes:jpg,jpeg,png',
            'email' => [
                'nullable',
                'email',
                function ($attribute, $value, $fail) {
                    if ($value && !str_contains($value, '@gmail.com')) {
                        $fail($attribute . __('api.gmail'));
                    }
                },
            ],
            'face_image' => 'required|mimes:jpg,jpeg,png',
            'back_image' => 'required|mimes:jpg,jpeg,png',
            'country' => 'nullable',
            'apps' => 'required',
            'salary' => 'required|integer',
            'host' => 'required|integer',
            'uuid' => 'nullable|exists:users,uuid',
            'video' => 'nullable|file|mimes:mp4,ogx,oga,ogv,ogg,webm'
        ]);

        if ($validator->fails()) {
            $errors = implode(',', $validator->errors()->all());
            return Common::apiResponse(0, $errors,  200);
        }
        if ($user->is_bd) return Common::apiResponse(false, 'You are BD ,You can\'t create agency', null, 407);
        try {
            $agencyWithAdditionalInfo =  $this->agencyService->create($user->id, $request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        $gmail = Common::getConfig('gmail');
        try {
            Mail::to($gmail)->send(new SendAgencyEmail($agencyWithAdditionalInfo));
        } catch (\Illuminate\Database\QueryException $e) {
            return Common::apiResponse(1, __("api_responses.created"),  200);
        }
        return Common::apiResponse(1, __("api_responses.created"),  200);
    }

    public function actionRequestAgency(Request $request)
    {
        try {
            $this->agencyService->actionRequestAgency($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if ($request->status != 1) {
            return Common::apiResponse(1, 'deleted request',  200);
        } else {
            return Common::apiResponse(1, 'accept request',  200);
        }
    }

    public function allAgencyRequest()
    {
        $agencies = $this->agencyService->allRequest();
        return Common::apiResponse(1, '', $agencies,  200);
    }

    public function agency_last_thirty_day()
    {
        $userUuid = \request('uuid');
        if (!$userUuid) return Common::apiResponse(0, 'missing parameters', []);
        $data = $this->agencyService->historyLastThirtyDays($userUuid);
        return Common::apiResponse(1, '', $data);
    }

    public function agency_total_reports()
    {
        $agencyId  = \request('agency_id');
        if (!$agencyId) return Common::apiResponse(0, 'missing parameters', []);

        $data = $this->agencyService->agencyReport($agencyId);
        return Common::apiResponse(1, '', $data);
    }

    public function leave_agency(Request $request)
    {
        $user = $request->user();
        if (!$user->agency) return Common::apiResponse(0, __("api_responses.agency"), []);
        $agency = $user->agency;
        if (date("d") >= 10) return Common::apiResponse(0, __('api_responses.leave_agency'), []);
        try {
            $this->agencyService->leaveAgency($user->id, $agency);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(0, __("api_responses.request_sent"), []);
    }

    public function historyDataAgency(Request $request)
    {
        $data = (new AgencyDataSearch())->fetchData($request);
        return Common::apiResponse(1, '', $data);
    }

    public function make_user_handling_requests(Request $request)
    {
        $user = $request->user();
        $agency = $user->ownAgency;
        if (!$user->ownAgency)   return Common::apiResponse(0, 'لا يوجد وكاله!', []);
        try {
            $this->agencyService->handlingRequest($agency->id, $request->user_id);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'تم اضافه المستخدم بنجاح', []);
    }

    public function kick_of_agency(Request $request)
    {
        $user = $request->user();
        $kickOutStartPerDays = Common::getConfig('kick_out_less_than_day') ?? 5;
        $kickOutEndPerDays = Common::getConfig('kick_out_greater_than_day') ?? 10;

        // if (Carbon::now()->day < $kickOutStartPerDays || Carbon::now()->day > $kickOutEndPerDays) return Common::apiResponse(0,__('api.kickRole', ['startDay' => $kickOutStartPerDays, 'endDay' => $kickOutEndPerDays], ), []);
        if (!$request->user_id) return Common::apiResponse(0, 'missing_parameters', 404);
        $user_kicked = User::find($request->user_id);
        try {
            $this->agencyService->kickAgency($user, $request->user_id);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'تم حذف المستخدم بنجاح', []);
    }

    public function agency_filter(Request $request)
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            // Return 422 instead of throwing Exception to prevent error log pollution
            // when host_agency feature is intentionally disabled
            return Common::apiResponse(0, __('Agency Feature is Disabled, Contact the administration'), null, 422);
        }

        $keyword = $request->keyword;
        [$agencies, $agencyManger] = $this->agencyService->filter($keyword);
        $data = [
            'agencies' => FilterAgancyResource::collection($agencies),
            'agency_masters' => FilterAgencyMangerResource::collection($agencyManger),
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function agencyFilterV2(Request $request): JsonResponse
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            return Common::apiResponse(0, __('Agency Feature is Disabled, Contact the administration'), null, 422);
        }

        $keyword = $request->keyword;
        $agencies = $this->agencyService->filterV2($keyword);

        return Common::apiResponse(1, '', FilterAgancyResource::collection($agencies));
    }

    public function masters(Request $request): JsonResponse
    {
        $app_feature = Cache::get('host_agency');
        if (!$app_feature) {
            return Common::apiResponse(0, __('Agency Feature is Disabled, Contact the administration'), null, 422);
        }

        $keyword = $request->keyword;
        $agencyMangers = $this->agencyService->agencyMangers($keyword);
        return Common::apiResponse(1, '', FilterAgencyMangerResource::collection($agencyMangers));
    }

    public function dailyReport()
    {
        $user  = \Auth::user();
        $month = request()->month ?? now()->format('m');
        $year = request()->year ?? now()->year;
        $agencyId = request()->agency_id ?? $user->agency_id;

        if (!$user instanceof User) return;
        $userId        = $user->id;

        $cacheKey = 'cache-data-my-store-' . $user->id;
        if (Cache::add($cacheKey, true, now()->addSeconds(30))) {
            $targetService = new FixedTargetService($user);
            ($targetService)->calculateTarget();
        }

        $data = $this->agencyService->dailyReport($user, $month, $year, $agencyId);
        $data = empty($data) ? new \stdClass() : $data;
        return Common::apiResponse(true, 'success', $data);
    }
}
