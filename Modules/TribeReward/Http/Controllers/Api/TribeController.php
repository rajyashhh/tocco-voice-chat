<?php

namespace Modules\TribeReward\Http\Controllers\Api;

use App\Helpers\Common;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TribeReward\Http\Requests\SendUserRewardRequest;
use Modules\TribeReward\Services\TribeService;
use Modules\TribeReward\Transformers\AgencyRankingResource;
use Modules\TribeReward\Transformers\AgencyRewardResource;
use Modules\TribeReward\Transformers\TribePeriodResource;

class TribeController extends Controller
{
    public function __construct(private readonly TribeService $tribeService)
    {
    }

    public function index(): JsonResponse
    {

        $tribePeriod = $this->tribeService->index();

        if (!$tribePeriod){
            return Common::apiResponse(true, '', null, 200);
        }

        return Common::apiResponse(true, '', TribePeriodResource::make($tribePeriod), 200);
    }

    public function agencyRanking(): JsonResponse
    {
        $agencyRanks = $this->tribeService->agencyRanking();

        if (! $agencyRanks){
            return Common::apiResponse(true, '', [], 200);
        }

        return Common::apiResponse(true, '', AgencyRankingResource::collection($agencyRanks), 200);
    }

    /**
     * @throws \Exception
     */
    public function agencyRewards(): JsonResponse
    {
        $agencyRewards = $this->tribeService->agencyRewards();

        return Common::apiResponse(true, '', AgencyRewardResource::collection($agencyRewards), 200);
    }

    /**
     * @throws \Exception
     */
    public function sendUserRewards(SendUserRewardRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $this->tribeService->sendUserRewards($data, $id);

        return Common::apiResponse(true, __('Rewards sent successfully'));
    }

}
