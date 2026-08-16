<?php

namespace Modules\Achievement\Http\Controllers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Achievement\Http\Services\UserAchievementService;
use Modules\Achievement\Transformers\UserAchievementLevelsResource;

class AchievementLevelController extends Controller
{

    public function __construct(private UserAchievementService $achievementService) { }


    /**
     * Show the specified resource.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::withoutAppends()->findOrFail($id);
        } catch (\Exception $e) {
            return Common::apiResponse(false, 'No user Founded');
        }

        $userAchievementLevels = $this->achievementService->getUserAchievement($user);

        return Common::apiResponse(true, 'success',UserAchievementLevelsResource::collection($userAchievementLevels));
    }


}
