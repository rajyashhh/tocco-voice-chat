<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Resources\Api\V1\UserResourceSearchV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\V1\RoomSearchResource;
use App\Http\Resources\Api\V1\UserResourceSerche;
use Modules\Public\Http\Services\UserCounterServices;
use App\Repositories\Community\SearchRepositoryInterface;

class CommunityController extends Controller
{

    protected $searchRepository;

    public function __construct(SearchRepositoryInterface $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }

    // search
    public function merge_search(Request $request)
    {
        $keywords = $request->keywords;
        $user_id = $request->user()->id;

        if (!$keywords || !$user_id) {
            return Common::apiResponse(0, 'Missing parameters');
        }

        $this->searchRepository->saveSearchHistory($user_id, $keywords);

        $result = ['user' => UserResourceSerche::collection($this->searchRepository->userSearchHand($user_id, $keywords)), 'rooms' => RoomSearchResource::collection($this->searchRepository->searchRooms($user_id, $keywords)),];

        return Common::apiResponse(1, '', $result, paginationKey: 'user');
    }

    public function mergeSearchV2(Request $request): JsonResponse
    {
        $keywords = $request->keywords;
        $user = Auth::user();
        $user_id = $user->id;

        if (!$keywords || !$user_id) {
            return Common::apiResponse(0, 'Missing parameters');
        }

        $this->searchRepository->saveSearchHistory($user_id, $keywords);

        $user->loadMissing(['blockedUsers', 'blockedMe']);
        $blockedByMe = $user->blockedUsers->pluck('from_uid')->toArray();
        $blockedMe = $user->blockedMe->pluck('user_id')->toArray();
        $blockedUserIds = array_unique(array_merge($blockedByMe, $blockedMe));

        $result = ['user' => UserResourceSearchV2::collection($this->searchRepository->userSearchHandV2($user_id, $keywords, $blockedUserIds)), 'rooms' => RoomSearchResource::collection($this->searchRepository->searchRoomsV2($user_id, $keywords, $blockedUserIds)),];

        return Common::apiResponse(1, '', $result, paginationKey: 'user');
    }

    // friends
    public function user_friends()
    {
        $userId = Auth::id();
        $keywords = request()->keywords;
        $perPage = 10;
        $currentPage = request()->has('page') ? request()->page : 1;

        $users = $this->searchRepository->getUserFriends($userId, $keywords, $perPage, $currentPage);
        $countUsers = $users->total();

        return Common::apiResponse(1, 'success', ['user' => UserResourceSerche::collection($users), 'number_of_friends' => $countUsers,]);
    }

    // search history
    public function searchList(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->searchRepository->getSearchList($userId);

        return Common::apiResponse(1, '', $data);
    }

    //clear search history
    public function cleanSearchList(Request $request)
    {
        $userId = $request->user()->id;
        $result = $this->searchRepository->clearUserSearchHistory($userId);

        if ($result) {
            return Common::apiResponse(1, 'Empty successfully');
        } else {
            return Common::apiResponse(0, 'Empty failed', null, 400);
        }
    }

    // official message
    public function officialMessages(Request $request)
    {
        $userId = $request->user()->id;
        if (!$userId) return Common::apiResponse(0, 'un_auth');

        $page = $request->page ?: 1;
        $data = $this->searchRepository->getOfficialMessages($userId, $page);

        // Update user counters
        (new UserCounterServices)->UpgradeDateForType($request->user(), 'official_message');
        (new UserCounterServices)->UpgradeDateForType($request->user(), 'system_message');

        return Common::apiResponse(1, '', $data);
    }

    public function notifications(Request $request): JsonResponse
    {
        // 0 => 'agency', 1 => 'sys', 2 => 'official',
        $data = $request->validate([
            'type' => 'required|integer|in:0,1,2',
        ]);

        $userId = $request->user()->id;
        if (!$userId) return Common::apiResponse(0, 'un_auth');

        $data = $this->searchRepository->getNotifications($userId,$data['type']);

        // Mark-seen watermark write. The two UserCounter rows gate the unread
        // "since date" count in UserCounterServices::getCountByType; they must
        // still advance to now() so subsequent badge counts are correct. The
        // writes do not affect THIS response body, so we defer them past the
        // response to keep this high-frequency poll GET non-blocking (was 2
        // updateOrCreate = ~4 round-trips on the request path). Behaviour is
        // identical to the inline calls — same rows, same now() value.
        $user = $request->user();
        dispatch(function () use ($user) {
            (new UserCounterServices)->UpgradeDateForType($user, 'official_message');
            (new UserCounterServices)->UpgradeDateForType($user, 'system_message');
        })->afterResponse();

        return Common::apiResponse(1, '', $data);
    }
}
