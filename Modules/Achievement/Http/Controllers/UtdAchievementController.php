<?php

namespace Modules\Achievement\Http\Controllers;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Achievement\Http\Services\AchievementService;
use Modules\Achievement\Transformers\GiftAchievementUser;
use Modules\Achievement\Transformers\UserAchievementLevelGiftResource;


class UtdAchievementController extends Controller
{
    public function __construct(private AchievementService $achievementService) {}

    public function allAchievements()
    {
        $data = $this->achievementService->all();
        return Common::apiResponse(true, 'done', $data);
    }

    public function allAchievementsLevel($achievementId, Request $request)
    {
        $data = $this->achievementService->allAchievementLevel($achievementId, $request->per_page, $request->Page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function createAchievementLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'achievement_id'         => 'required|integer|exists:achievements,id',
            'target' => 'required|integer',
            'target_type' => 'required|string',
            'valid_image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'invalid_image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ar_description' => 'nullable',
            'en_description' => 'nullable',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->achievementService->createAchievementLevel($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateAchievementLevel($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'achievement_id'         => 'required|integer|exists:achievements,id',
            'target' => 'required|integer',
            'target_type' => 'required|string',
            'valid_image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'invalid_image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ar_description' => 'nullable',
            'en_description' => 'nullable',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->achievementService->updateAchievementLevel($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function deleteAchievementLevel($id)
    {

        try {
            $this->achievementService->deleteAchievementLevel($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function showAchievementLevel($id)
    {
        try {
            $data  = $this->achievementService->showAchievementLevel($id);

            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function achievementTargetType()
    {
        try {
            $targetTypes = $this->achievementService->achievementTargetType();
            return Common::apiResponse(true, 'done', $targetTypes);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allUsersGiftAchievements($achievementId, Request $request)
    {

        $data = $this->achievementService->allAchievementGift($achievementId, $request->per_page, $request->Page);
        return Common::apiResponse(true, 'done', GiftAchievementUser::collection($data));
    }

    public function createUserAchievementGift(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'achievement_id'         => 'required|integer|exists:achievements,id',
            'user_id'         => 'required|integer|exists:users,id',
            'gift_id'         => 'required|integer|exists:gifts,id',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->achievementService->achievementGift($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function giftAchievement(Request $request)
    {
        try {
            $data = $this->achievementService->giftAchievement();
            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allUserAchievementLevel(Request $request)
    {
        $data = $this->achievementService->userAchievementLevel($request->per_page, $request->Page, $request->uuid);
        return Common::apiResponse(true, 'done', $data);
    }

    public function isEnable($id, Request $request)
    {
        $this->achievementService->isEnable($id, $request->is_enable);
        return Common::apiResponse(true, 'changed');
    }

    public function deleteUserAchievementLevel($id)
    {
        try {
            $this->achievementService->deleteUserAchievementLevel($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function createUserAchievementLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'achievement_id'         => 'required|integer|exists:achievements,id',
            'achievement_level_id'         => 'nullable|integer|exists:achievement_levels,id',
            'user_id'         => 'required|integer|exists:users,id',
            'gift_achievement_id'         => 'nullable|integer',
            'custom_image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,svga',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->achievementService->createUserAchievementLevel($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userAchievementLevelGiftIndex(Request $request)
    {
        $data = $this->achievementService->giftAchievementIndex($request->per_page, $request->Page);
        return Common::apiResponse(true, 'done', UserAchievementLevelGiftResource::collection($data));
    }

    public function getAchievementLevelsTarget($achievementId)
    {
        $data = $this->achievementService->getAchievementLevelsTarget($achievementId);
        return Common::apiResponse(true, 'done', $data);
    }
}
