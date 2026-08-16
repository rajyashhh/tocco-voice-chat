<?php

namespace Modules\CP\Http\Controllers\Api;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CP\Http\Resources\TopWeeklyCpResource;
use Modules\CP\Http\Services\WeeklyCpService;
use Modules\CP\Http\Resources\WeeklyCpResource;
use Modules\CP\Http\Resources\UserWeeklyCpResource;
use Modules\CP\Http\Resources\PerviousWeeklyCpResource;
use Modules\CP\Http\Resources\PerviousOneWeeklyCpResource;


class WeeklyCpController extends Controller
{
    public function __construct(private WeeklyCpService $weeklyCpService) {}

    public function perviousWeeklyCpWinners()
    {
        try {
            $data = $this->weeklyCpService->perviousCpWinners();
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        return Common::apiResponse(1, '', PerviousWeeklyCpResource::collection($data));
    }

    public function weeklyCpDetails()
    {
        try {
            [$weeklyCp, $rule] = $this->weeklyCpService->weeklyCpDetails();
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        $data = new WeeklyCpResource($weeklyCp, $rule);
        return Common::apiResponse(1, '', $data);
    }

    public function topUsers(Request $request)
    {

        try {
            $data = $this->weeklyCpService->topUsers();
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }

        return Common::apiResponse(1, 'success', TopWeeklyCpResource::collection($data));
    }

    public function topOnePerviousWeeklyCp()
    {
        try {
            $data = $this->weeklyCpService->topOneCurrentWeeklyCp();
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        return Common::apiResponse(1, '', new PerviousOneWeeklyCpResource($data));
    }

    public function userDetails(Request $request)
    {
        $user = $request->user();
        try {
            $data = $this->weeklyCpService->userDetails($user);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        return Common::apiResponse(1, '', new UserWeeklyCpResource($user, $data));
    }
}
