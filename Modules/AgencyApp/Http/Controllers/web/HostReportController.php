<?php

namespace Modules\AgencyApp\Http\Controllers\web;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Tik\Services\AgencyService;
use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Services\FixedTargetService;

class HostReportController extends Controller
{
    protected $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return Common::apiResponse(false, 'missing user_id parameter', null, 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return Common::apiResponse(false, 'user not found', null, 404);
        }

        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->year);
        $agencyId = $request->input('agency_id', $user->agency_id);

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
