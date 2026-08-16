<?php

namespace Modules\RoomBoom\Http\Controllers\Api;

use App\Helpers\Common;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\RoomBoom\Services\RoomBoomLevelService;
use Modules\RoomBoom\Transformers\PercentageBoomResource;
use Modules\RoomBoom\Transformers\RoomBoomLevelResource;
use Modules\RoomBoom\Transformers\ThemesBoomRoomLevelResource;

class RoomBoomLevelController extends Controller
{
    public function __construct(private readonly RoomBoomLevelService $roomBoomLevelService) {}

    public function index($id): JsonResponse
    {
        $roomBoomLevels = $this->roomBoomLevelService->index($id);

        return Common::apiResponse(true, '', RoomBoomLevelResource::collection($roomBoomLevels), 200);
    }

    public function getVideos(): JsonResponse
    {
        $roomBoomLevels = $this->roomBoomLevelService->getVideos();

        return Common::apiResponse(true, '', RoomBoomLevelResource::collection($roomBoomLevels), 200);
    }

    public function roomThemes()
    {
        [$roomBoom, $BoomPercentage] = $this->roomBoomLevelService->boomPercentage();
        $data = [
            'levels' => ThemesBoomRoomLevelResource::collection($roomBoom),
            'progress_animations' => PercentageBoomResource::collection($BoomPercentage)
        ];
        return Common::apiResponse(true, '', $data, 200);
    }
}
