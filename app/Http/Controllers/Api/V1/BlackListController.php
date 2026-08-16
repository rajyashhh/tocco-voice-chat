<?php

namespace App\Http\Controllers\Api\V1;

use App\helper\UserFollowHelper;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\BlackList;
use App\Models\Follow;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlackListController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    public function index(Request $request){
        $user = $request->user();
        $blacks = $this->userService->getUserBlackList($user->id);
        return Common::apiResponse (1,'',UserResource::collection ($blacks),200);
    }

    public function remove(Request $request)
    {
        if (!$request->user_id) {
            return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        }

        $me = $request->user();

        $this->userService->removeUserFromBlackList($me->id, $request->user_id);

        $userList = $this->userService->getUserBlackList($me->id);

        return Common::apiResponse(1, __('api_responses.done'), UserResource::collection($userList), 200);
    }

    public function add(Request $request){
        if (!$request->user_id) return Common::apiResponse (0,__('api_responses.missing_params'),null,422);
        $me = $request->user ();
        DB::beginTransaction ();
        try {
            BlackList::query ()->create (
                [
                    'user_id'=>$me->id,
                    'from_uid'=>$request->user_id
                ]
            );
            Follow::query ()->where ('user_id',$me->id)->where ('followed_user_id',$request->user_id)->delete ();
            Follow::query ()->where ('user_id',$request->user_id)->where ('followed_user_id',$me->id)->delete ();

            UserFollowHelper::updateCounts($me);
            $blockedUser = User::query()->find($request->user_id);
            if ($blockedUser) {
                UserFollowHelper::updateCounts($blockedUser);
            }

            DB::commit ();
            return Common::apiResponse (1,__('api_responses.done'),UserResource::collection ($this->getList ($me->id)),200);

        }catch (\Exception $exception){
            DB::rollBack ();
            return Common::apiResponse (0,__('api_responses.failed'),null,400);
        }
    }

    public function getList($user_id){
        $black_list = Common::getUserBlackList ($user_id);
        $blacks = User::query ()->whereIn ('id',$black_list)->get ();
        return UserResource::collection ($blacks);
    }

    public function checkBlockStatus(int $userId)
    {
        $currentUserId = Auth::id();
        $isBlocked = BlackList::query ()->where(fn ($query) => $query->where('user_id', $currentUserId)->where('from_uid', $userId))
                              ->orWhere(fn ($query) => $query->where('user_id', $userId)->where('from_uid', $currentUserId))
                              ->exists();
        return Common::apiResponse(true, 'success', ['is_blocked' => $isBlocked]);
    }
}
