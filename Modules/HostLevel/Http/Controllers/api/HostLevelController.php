<?php

namespace Modules\HostLevel\Http\Controllers\api;


use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HostLevel\Http\Services\HostLevelService;
use Modules\HostLevel\Transformers\HostLevelResource;
use Modules\HostLevel\Transformers\UserHostLevelResource;


class HostLevelController extends Controller
{

    public function __construct(private HostLevelService $hostLevelService) {}

    public function hostLevel()
    {
        $user = request()->user();
        $data = $this->hostLevelService->hostLevelIndex();
        $rule = $this->hostLevelService->roles();
        $field = "desc_" . app()->getLocale();


        [$diamonds, $nextLevel, $currentLevel, $level, $eventType, ] = $this->hostLevelService->userInfoLevel($user);
        request()->merge(['userDiamonds' => $diamonds, 'nextLevel' => $level]);
        $data = [
            'levels' => HostLevelResource::collection($data),
            'roles' => $rule != null ? $rule->$field : "",
            'event_type' => $eventType == 'daily' ? 1 : ($eventType == 'weekly' ? 2 : 3),
            'user' => [
                'name' => $user->name ?? '',
                'image' => $user->profile->avatar ?? '',
                'current_level' => $currentLevel ?? 0,
                'next_level' => $nextLevel ?? 0,
                'diamonds' => $diamonds,
                'last_level' => $level ?? 0,
            ],
        ];
        return Common::apiResponse(true, '', $data, 200, '', 'levels');
    }

    public function pick(Request $request)
    {
        $user = $request->user();
        $hostLevelId = $request->host_level_id;
        if (!$hostLevelId) {
            return Common::apiResponse(false, __('host level id is required'), null, 407);
        }
        try {

            $this->hostLevelService->pickHostLevel($user, $hostLevelId);
        } catch (\Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(true, __('success process'));
    }
}
