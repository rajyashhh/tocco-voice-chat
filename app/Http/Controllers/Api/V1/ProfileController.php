<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ProfileRequest;
use App\Http\Resources\Api\V1\NewProfileResource;
use App\Http\Resources\Api\V1\UserRelationsResource;
use App\Http\Resources\Api\V1\UserVisitorResource;
use App\Models\Follow;
use App\Models\User;
use App\Rules\ImageSizeRule;
use App\Services\ProfileService;
use App\Services\UserService;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    protected $profileService;
    protected $userService;

    public function __construct(ProfileService $profileService, UserService $userService)
    {
        $this->profileService = $profileService;
        $this->userService = $userService;
    }

    public function update(ProfileRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id'       => 'nullable|numeric|exists:countries,id',
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', new ImageSizeRule(),],

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $out = $this->profileService->updateProfile($request);

            return Common::apiResponse(true, __('profile updated successfully'), $out, 200);
        } catch (Exception $e) {

            return Common::apiResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function show(Request $request, $id)
    {
        $me = $request->user();
        return $this->profileService->showProfile($me, $id);
    }

    public function liked(Request $request)
    {
        $userId = Auth::id();
        $likedUserId = $request->user_id;

        $message = $this->userService->toggleLike($userId, $likedUserId);

        return Common::apiResponse(true, $message, 200);
    }

    public function ignored(Request $request)
    {
        $userId = Auth::id();
        $ignoreUserId = $request->user_id;

        $message = $this->userService->toggleIgnored($userId, $ignoreUserId);

        return Common::apiResponse(true, $message, 200);
    }

    // public function users(Request $request)
    // {
    //     $user = Auth::user();
    //     $latitude = $user->lat;
    //     $longitude = $user->long;
    //     $users = User::query()->select('users.*')
    //         ->selectRaw(
    //             "(6371 * acos(cos(radians(?)) * cos(radians(users.lat)) * cos(radians(users.long) - radians(?)) + sin(radians(?)) * sin(radians(users.lat)))) AS distance",
    //             [$latitude, $longitude, $latitude]
    //         )
    //         ->where('users.id', '!=', $user->id)
    //         ->whereDoesntHave('ignores', fn($q) => $q->where("ignore_user_id", $user->id))
    //         ->withExists(['likedBy' => fn($q) => $q->where("liked_user_id", $user->id)])
    //         ->orderBy('distance')
    //         ->paginate(10);

    //     return $users;
    //     return Common::apiResponse(true,'', NewProfileResource::collection($users), 200);
    // }

    public function getNearbyUsers($userId, $distance = 10)
    {
        $user = Auth::user();
        $users = $this->profileService->getNearbyUsers($user);
        return Common::apiResponse(true, '', NewProfileResource::collection($users), 200);
    }


    public function myProfileVisitorsList(Request $request)
    {
        $user = $request->user();
        $keyword = $request->keywords ?? '';

        [$profileVisitors, $userFollowers, $senderLevels, $receivedImage] = $this->profileService->getProfileVisitorsList($user, $keyword);
        UserVisitorResource::initializeData($senderLevels, $receivedImage, $userFollowers);

        $visitors = UserVisitorResource::collection($profileVisitors);

        UserVisitorResource::clear();
        return Common::apiResponse(1, '', $visitors);
    }

    public function related()
    {
        $randomUsers = $this->profileService->getRelatedUsers(20);

        return Common::apiResponse(true, '',  UserRelationsResource::collection($randomUsers), 200);
    }

    public function getFollowingUsers()
    {
        $followedIds = Follow::query()->whereHas('followed')->where('user_id', Auth::id())->pluck('followed_user_id');
        $users = User::query()->with([
            'profile', 'UserVip', 'mangerType', 'specialId.ware', 'country',
            'images', 'chatRoomsAsUser', 'chatRoomsAsUser2',
        ])->whereIn("id", $followedIds)->get();
        return Common::apiResponse(true, '', NewProfileResource::collection($users), 200);
    }
}
