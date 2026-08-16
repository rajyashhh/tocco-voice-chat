<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\Room\RoomComments;
use App\Exceptions\NotInfCoins;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\CommentRequest;
use App\Http\Requests\EditRoomRequest;
use App\Http\Resources\Api\V1\BoxUseResource;
use App\Http\Resources\Api\V1\EnterRoomCollection;
use App\Http\Resources\Api\V1\RoomAdminsResource;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\Api\V1\RoomVisitorsResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\GiftRoomResource;
use App\Http\Resources\RoomCountriesResource;
use App\Http\Resources\RoomDetailsResource;
use App\Http\Services\ProfileRelationsService;
use App\Jobs\EnterRoomZigoRequest;
use App\Models\AllGame;
use App\Models\EnteredRoom;
use App\Models\KickRecord;
use App\Models\LiveTime;
use App\Models\Pk;
use App\Models\RequestBackgroundImage;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomMicrophone;
use App\Models\User;
use App\Repositories\Room\RoomRepoInterface;
use App\Rules\ImageSizeRule;
use App\Services\RoomService;
use App\Tik\Services\RoomRepoService;
use App\Traits\MultiQueryPagination;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Achievement\Http\Services\UserAchievementService;
use Modules\CP\Entities\CpRoomHistory;
use Modules\LuckyBox\Entities\BoxUse;
use Modules\RoomBoom\Entities\RoomBoom;
use Modules\RoomBoom\Transformers\RoomBoomLevelResource;
use Modules\RoomBoom\Transformers\RoomBoomResource;
use Throwable;

class RoomController extends Controller
{
    use MultiQueryPagination;

    protected $repo;
    protected $roomService;
    protected $roomServiceMain;

    public function __construct(
        RoomRepoInterface $repo,
        RoomRepoService $roomService,
        RoomService $roomServiceMain,

    ) {
        $this->repo = $repo;
        $this->roomService = $roomService;
        $this->roomServiceMain = $roomServiceMain;
    }

    public function sendPrivateComment(Request $request, int $ownerId)
    {
        $validator = Validator::make($request->all(), [
            "message" => "required",
            "to_user_id" => "required|exists:users,id",
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(' , ', $validator->errors()->all()), $validator->errors(), 422);
        }

        //user id how send and recieved this message
        $fromUser = Auth::user();
        $fromUserId      = $fromUser->id;
        $toUserId        = $request->to_user_id;
        $message = $request->message;
        try {
            [$toUser, $price] =   $this->roomService->privateComment($toUserId, $message, $ownerId, $fromUser);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        return Common::apiResponse(true, 'success', [
            'message' => (@$toUser->name ?? 'name') . ': ' . $message,
            'price' => $price,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
        ]);
    }

    public function index(Request $request)
    {
        $audioRoom = Common::getSettingValue('audio_room') ?? 1;
        // if ($request->has('room_type')  && $request->room_type == 'audio' && !$audioRoom) {
        //     return Common::apiResponse(false, 'Room type not matched', null, 400);
        // }
        if (!$audioRoom) return Common::apiResponse(false, 'Audio rooms are disabled', null, 400);

        // Per-request default background is identical for every caller and changes
        // rarely; cache it instead of hitting the DB on this hottest endpoint.
        request()->default_background = \Illuminate\Support\Facades\Cache::remember(
            'rooms:default_background',
            300,
            fn() => optional(
                \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()
            )->img
        );

        $userId = Auth::id();

        // Filters whose result set depends on the authenticated viewer (their
        // entered rooms / follows / friends / location). These can never share a
        // cache entry across viewers and stay on the legacy per-user key.
        $perUserFilters = ['boss', 'interested', 'following', 'friends', 'nearby'];
        $sharedEligible = !in_array($request->filter, $perUserFilters, true);

        // Shared cache path: the heavy room list is identical for every viewer on
        // the shared-eligible filters, so we cache it ONCE (no userId in the key)
        // and overlay the single genuinely per-viewer field (is_lucky_box) per
        // request on a deep clone of the cached array. A feature flag instantly
        // reverts to the legacy per-user key without a redeploy.
        if ($sharedEligible && config('rooms.shared_cache_enabled')) {
            return response()->json(
                $this->filterBannedRooms($this->sharedRoomIndex($request, $userId), $userId),
                200
            );
        }

        // Legacy per-user path (per-user filters, or flag off). The key includes
        // every input that changes the result so distinct callers/pages never
        // collide. We cache the rendered array (not the paginator/resources) so
        // the JSON shape stays byte-identical to the uncached path.
        $cacheKey = 'rooms:idx:' . $userId . ':' . md5(json_encode([
            $request->room_type,
            $request->filter,
            $request->country_id,
            (int) ($request->page ?? 1),
        ]));

        $payload = \Illuminate\Support\Facades\Cache::remember($cacheKey, 20, function () use ($request) {
            $rooms = $this->roomService->getAllRooms($request);
            return Common::apiResponse(true, '', RoomResource::collection($rooms), 200)->getData(true);
        });

        return response()->json($this->filterBannedRooms($payload, $userId), 200);
    }

    /**
     * Shared room-list cache for the heavy, viewer-independent filters.
     *
     * The serialized payload is cached ONCE for all viewers under a key that
     * excludes the userId. The only genuinely per-viewer field, is_lucky_box
     * (whether THIS viewer still has an unpicked active box in the room), is
     * recomputed cheaply and overlaid per request on a DEEP CLONE of the cached
     * array — the cached structure itself is never mutated. A Cache::lock + TTL
     * jitter prevents a hot-key expiry from stampeding the DB.
     */
    private function sharedRoomIndex(Request $request, $userId): array
    {
        $page = (int) ($request->page ?? 1);
        $sharedKey = 'rooms:idx:v2:' . md5(json_encode([
            $request->room_type,
            $request->filter,
            $request->country_id,
            $page,
        ]));
        $lockKey = $sharedKey . ':lock';

        $payload = \Illuminate\Support\Facades\Cache::get($sharedKey);

        if ($payload === null) {
            // Single-flight rebuild: only one worker queries the DB on a cold
            // key; the rest block briefly for the result rather than stampeding.
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);
            try {
                if ($lock->get()) {
                    $payload = \Illuminate\Support\Facades\Cache::get($sharedKey)
                        ?? $this->buildSharedRoomPayload($request, $sharedKey);
                } else {
                    $lock->block(5, function () use (&$payload, $sharedKey, $request) {
                        $payload = \Illuminate\Support\Facades\Cache::get($sharedKey)
                            ?? $this->buildSharedRoomPayload($request, $sharedKey);
                    });
                }
            } finally {
                optional($lock)->release();
            }
        }

        // Deep clone before stamping so the shared cached array is never mutated
        // (json round-trip is the cheapest faithful deep copy of this nested
        // array-of-arrays and matches the serialized shape exactly).
        $payload = json_decode(json_encode($payload), true);

        return $this->overlayLuckyBox($payload, $userId);
    }

    /**
     * Build + cache the shared (viewer-independent) room-list payload. In this
     * path is_lucky_box carries a room-level "room HAS an active box" value;
     * overlayLuckyBox() corrects it to the per-viewer truth on read.
     */
    private function buildSharedRoomPayload(Request $request, string $sharedKey): array
    {
        $rooms = $this->roomService->getAllRooms($request, true);
        $payload = Common::apiResponse(true, '', RoomResource::collection($rooms), 200)->getData(true);

        // TTL jitter so sibling keys (and replicas) don't all expire on the same
        // tick and re-stampede the DB at once.
        \Illuminate\Support\Facades\Cache::put($sharedKey, $payload, 20 + random_int(0, 10));

        return $payload;
    }

    /**
     * Overlay the per-viewer is_lucky_box flag on a (already-cloned) payload.
     * is_lucky_box is true when the room has an active, unexhausted box that
     * THIS viewer has not yet picked from — mirroring the legacy
     * withLuckyBoxFlag scope semantics exactly.
     */
    private function overlayLuckyBox(array $payload, $userId): array
    {
        if (empty($payload['data']) || !is_array($payload['data'])) {
            return $payload;
        }

        $roomIds = [];
        foreach ($payload['data'] as $room) {
            if (isset($room['id'])) {
                $roomIds[] = $room['id'];
            }
        }
        if (empty($roomIds)) {
            return $payload;
        }

        // One query: room_ids on this page that have an active box this viewer
        // has NOT picked from. Everything else is false.
        $luckyRoomIds = BoxUse::query()
            ->whereIn('room_id', $roomIds)
            ->where('not_used_num', '>', 0)
            ->where('end_at', '>=', now()->timestamp)
            ->whereDoesntHave('picks', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->distinct()
            ->pluck('room_id')
            ->flip();

        foreach ($payload['data'] as &$room) {
            if (isset($room['id'])) {
                $room['is_lucky_box'] = $luckyRoomIds->has($room['id']);
            }
        }
        unset($room);

        return $payload;
    }

    /**
     * Drop rooms the viewer is PERMANENTLY banned from out of the home list.
     * Per-viewer overlay on an already-cloned payload (never mutates the shared
     * cache). Only permanent bans hide the room — a timed kick keeps the room
     * visible (the viewer is just blocked at enter_room until it expires). The
     * banned set per viewer is tiny, so this adds one indexed query bounded to
     * the room_ids already on the page.
     */
    private function filterBannedRooms(array $payload, $userId): array
    {
        if (!$userId || empty($payload['data']) || !is_array($payload['data'])) {
            return $payload;
        }

        $roomIds = [];
        foreach ($payload['data'] as $room) {
            if (isset($room['id'])) $roomIds[] = $room['id'];
        }
        if (empty($roomIds)) return $payload;

        $bannedRoomIds = \App\Models\RoomBlacklist::whereIn('room_id', $roomIds)
            ->where('user_id', $userId)
            ->whereNull('expires_at') // permanent only
            ->where('is_active', true)
            ->pluck('room_id')
            ->flip();

        if ($bannedRoomIds->isEmpty()) return $payload;

        $payload['data'] = array_values(array_filter(
            $payload['data'],
            fn($room) => !isset($room['id']) || !$bannedRoomIds->has($room['id'])
        ));

        return $payload;
    }



    public function mine(Request $request)
    {
        $user_id = request('user_id') ?? Auth::user()->id;
        request()->default_background = \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img;
        $rooms = $this->roomService->getAllMine($request, $user_id);
        return Common::apiResponse(true, '', $rooms, 200);
    }

    public function userRoom($id, Request $request)
    {

        request()->default_background = \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img;
        $rooms = $this->roomService->getUserRooms($request, $id);
        return Common::apiResponse(true, '', $rooms, 200);
    }




    public function getAllLiveRooms(Request $request)
    {
        request()->default_background = \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img;
        $rooms = $this->roomService->getAllLiveRooms($request);
        return Common::apiResponse(true, '', $rooms, 200);
    }

    /**
     * Host explicitly ends their live broadcast: delist it IMMEDIATELY
     * (is_live=0 + clear presence rows) instead of waiting for the
     * occupancy sync / webhooks. Owner-only; the in-room clients are
     * ejected by the kit's _live_ended broadcast.
     */
    public function endLive(Request $request)
    {
        $room = \App\Models\Room::where('uid', $request->user()->id)
            ->where('type', '!=', 'audio')
            ->first();

        if (!$room) {
            return Common::apiResponse(false, __('Room not found.'), null, 404);
        }

        app(\App\Tik\Services\RoomOccupancyReconciler::class)->deactivateRoom($room);
        if ($room->is_afk) {
            $room->is_afk = 0;
            $room->save();
        }

        return Common::apiResponse(true, '', null, 200);
    }
    public function room_countries()
    {
        $data = $this->roomService->index2();
        return RoomCountriesResource::collection($data);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'room_cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', new ImageSizeRule(),],

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        $request['show']  = true;
        $request['numid'] = rand(111111, 999999);
        $user = $request->user();

        try {
            $roomType = $request->type ?? 'audio';

            if (!in_array($roomType, ['audio', 'live'])) {
                return Common::apiResponse(false, 'Room type not matched', null, 400);
            }

            $room = $this->roomService->findRoomUserByType($user->id, $roomType);
            if ($room) {
                return Common::apiResponse(true, "You already have a room of type {$roomType}", new RoomResource($room), 200);
            }

            $room = $this->roomService->create($request, $user);

            return Common::apiResponse(true, 'created', new RoomResource($room), 200);
        } catch (Exception $exception) {
            return Common::apiResponse(false, $exception->getMessage(), null, 400);
        }
    }
    private function getTimezone(): string
    {
        $tz = request()->header('tz', Common::timeZone());
        return in_array($tz, timezone_identifiers_list()) ? $tz : 'UTC';
    }

    public function extraRoomData($owner_id): JsonResponse
    {
        $room = $this->roomService->findAudioRoomUser($owner_id);
        if (!$room) return Common::apiResponse(false, 'No Room Founded');

        $tz = getTimezone();
        $today = Carbon::today($tz);

        $openBoom = RoomBoom::whereHas('totalRoomGift', function ($q) use ($room) {
            $q->where('room_id', $room->id);
        })
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->whereDate('started_at', $today)
            ->first();

        // Charisma is client-side now: it starts at 0 on room open and lives in
        // seat state, so no initial fetch is shipped here.
        $collections = [
            'achievements'      => $this->achievementLevels($owner_id),
            'boxes'             => BoxUseResource::collection($this->getBoxes($owner_id, Auth::id())),
            'open_boom'       => $openBoom ? new RoomBoomResource($openBoom) : null,
        ];
        return Common::apiResponse(true, 'successfully', $collections);
    }

    public function extraDataRoom(Request $request)
    {

        $roomId = $request->room_id;
        $room = $roomId
            ? Room::find($roomId)
            : Room::where('uid', $request->owner_id)->where('type', 'audio')->first();
        if (!$room) return Common::apiResponse(false, 'No Room Founded');
        $owner_id = $room->uid;
        $tz = getTimezone();
        $today = Carbon::today($tz);

        $openBoom = RoomBoom::whereHas('totalRoomGift', function ($q) use ($room) {
            $q->where('room_id', $room->id);
        })
            ->whereNull('ended_at')
            ->whereNotNull('started_at')
            ->whereDate('started_at', $today)
            ->first();

        // Charisma is client-side now: it starts at 0 on room open and lives in
        // seat state, so no initial fetch is shipped here.
        $collections = [
            'achievements'      => $this->achievementLevels($owner_id),
            'boxes'             => BoxUseResource::collection($this->getBoxes($room->id, Auth::id())),
            'open_boom'       => $openBoom ? new RoomBoomResource($openBoom) : null,
        ];
        return Common::apiResponse(true, 'successfully', $collections);
    }

    private function getBoxes($roomId, $userId)
    {
        $boxes = BoxUse::query()
            ->with([
                'user' => fn($q) => $q->with('profile')->withoutAppends()->select(['id', 'name', 'uuid']),
                'box:id,coins',
            ])
            ->where('room_id', $roomId)
            ->where('not_used_num', '>', 0)
            //            ->where('unused_coins', '>', 0)
            ->where('end_at', '>=', now()->timestamp)
            ->whereDoesntHave('picks', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('is_closed', 0)
            ->get();

        // withoutAppends() above flips a global static on the User model that is
        // never cleared; reset it so later User serialization in this request
        // (e.g. open_boom owner) is not silently stripped of its appended attrs.
        User::$withoutAppends = false;

        // Batch the per-box is_follow lookup into ONE query instead of one query
        // per box inside BoxUseResource (N+1). Resource reads the shared map.
        $boxUserIds = $boxes->pluck('user.id')->filter()->unique()->values();
        $followedIds = [];
        if ($userId && $boxUserIds->isNotEmpty()) {
            $followedIds = \App\Models\Follow::query()
                ->where('user_id', $userId)
                ->where('status', 1)
                ->whereIn('followed_user_id', $boxUserIds)
                ->pluck('followed_user_id')
                ->all();
        }
        BoxUseResource::initializeFollowMap($userId, $followedIds);

        return $boxes;
    }


    private function achievementLevels(int $owner_id)
    {
        return (new UserAchievementService())->roomAchievement($owner_id);
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        $request['show'] = true;
        // Heavy eager-load is isolated to this room-detail path only.
        $room = $this->roomService->findRoomForShow($id);
        if (!$room) {
            return Common::apiResponse(0, 'not found', null, 404);
        }
        return Common::apiResponse(true, '', new RoomResource($room), 200);
    }


    public function getAdmins(Request $request)
    {

        //if (!$request->owner_id && !$request->room_id) return Common::apiResponse(0, 'missing params', null, 422);
        try {
            $admins = $this->roomService->roomAdmins($request->id);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }

        $data = RoomAdminsResource::collection($admins);
        return Common::apiResponse(1, '', $data, 200);
    }


    //------------------------------------------------------------------ops----------------------------------------------------------------

    public function get_room_by_owner_id(Request $request)
    {
        $request['show'] = true;
        $room            = Room::where('uid', $request->owner_id)->first();
        if (!$room) {
            return Common::apiResponse(0, 'not found', null, 404);
        }
        return Common::apiResponse(true, '', new RoomResource($room), 200);
    }


    //get_room_by_owner_id

    public function amIHaveRoom(Request $request)
    {
        $room = Room::query()->where('uid', $request->user()->id)->exists();
        if ($room) {
            return Common::apiResponse(1, 'have a room', null, 200);
        }
        return Common::apiResponse(0, 'does not have a room', null, 200);
    }

    public function quit_room(Request $request)
    {
        if (!$request->owner_id && !$request->room_id) {

            return Common::apiResponse(false, __('missing parameter'), null, 422);
        }
        try {
            $user            = $request->user();
            // Charisma is client-side seat state now: no backend reset/broadcast on quit.
            [$visitorIdsList, $roomId] = $this->roomService->quiteRoom($request->owner_id, $user, $request->room_id);
            $this->handleLeaveCp($user, $roomId);
            $room = Room::find($roomId);
            if ($user->id === $room->uid) {
                $room->is_afk = 0;
                $room->save();
            }
            $this->updateMicrophone($room->uid, $user->id);
            return Common::apiResponse(true, 'exited', ['visitor_ids_list' => $visitorIdsList]);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function quit_room_2(Request $request)
    {
        if (!$request->owner_id && !$request->room_id) {

            return Common::apiResponse(false, __('missing parameter'), null, 422);
        }
        try {
            $user = $request->user();
            // Charisma is client-side seat state now: no backend reset/broadcast on quit.
            [$visitorIdsList, $roomId] = $this->roomService->quiteRoom2($request->owner_id, $user, $request->room_id);
            $this->handleLeaveCp($user, $roomId);
            $room = Room::find($roomId);
            if ($user->id === $room->uid) {
                $room->is_afk = 0;
                $room->save();
            }
            $this->updateMicrophone2($room->uid, $user->id);
            $this->deleteOldRoomMic($user, $room);
            return Common::apiResponse(true, 'exited', ['visitor_ids_list' => $visitorIdsList]);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function deleteOldRoomMic($user, $room)
    {
        RoomMicrophone::where('user_id', $user->id)->delete();
        CpRoomHistory::where("user_one_id", $user->id)
            ->orWhere("user_two_id", $user->id)->delete();
        $partner = $user->lovelyRelations()
            ->with(['userOne', 'userTwo'])
            ->first()?->partner;
        $json = $this->cpMapJson([]);

        if ($partner) Common::sendToStream('SendCustomCommand', @$partner->now_room_uid, $partner->id, $json);
    }


    public function handleLeaveCp($user, $roomId)
    {
        $userId = $user->id;
        $this->removeUserCpInRoom($userId);
        return $this->sendCpLovelyMessage($roomId, $user);
    }
    public function removeUserCpInRoom(mixed $userId): void
    {
        CpRoomHistory::where("user_one_id", $userId)
            ->orWhere("user_two_id", $userId)->delete();
    }

    public function sendCpLovelyMessage($roomId, $user)
    {
        $cpRoomHistories = CpRoomHistory::where("room_id", $roomId)->get(['index1', 'index2']);
        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        $json = $this->cpMapJson($indices);

        Common::sendToStream('SendCustomCommand', $roomId, $user->id, $json);
    }
    public function cpMapJson($indices): string|false
    {
        $ms = [
            'messageContent' => [
                "message" => "cpLovelyZego",
                "data" => $indices,
            ]
        ];
        $json = json_encode($ms);
        return $json;
    }
    public function calcTime($uid)
    {

        // case 1 : up_mic and go_mic in the same day
        $user  = User::find($uid);
        $timer =
            LiveTime::query()->where('uid', $uid)->whereDate('created_at', today())->where('end_time', null)->orderByDesc('id')->first();
        if ($timer) {
            $hours           = round((time() - $timer->start_time) / (60 * 60), 2);
            $timer->end_time = time();
            $timer->hours    = $hours;
            $timer->save();
            //$user_day = UserDay::where('user_id', $uid)->whereDate('created_at', today())->first();
            $user_hours =
                LiveTime::query()->where('uid', $user->id)->whereYear('created_at', '=', Carbon::now()->year)->whereMonth('created_at', '=', Carbon::now()->month)->whereDay('created_at', '=', Carbon::now()->day)->sum('hours');


            $hours = (int)$user_hours;

            if ($hours >= 1 && $user->today_days == 0) {
                DB::statement("
                UPDATE users
                SET today_days = 1
                WHERE id = :id
            ", ['id' => $user->id]);
            }
        }
    }

    public function getRoomUsers(Request $request, ProfileRelationsService $profileRelationsService)
    {
        $uid  = $request->owner_id;
        try {
            [$allData, $roomAdminActive] = $this->roomService->roomUsers($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        [$senderLevels, $receivedImage] = $profileRelationsService->getLevelsSenderAndReceiver($allData);
        RoomVisitorsResource::initializeData($senderLevels, $receivedImage, $roomAdminActive, $uid);
        $data = RoomVisitorsResource::collection($allData);

        return Common::apiResponse(1, '', $data);
    }

    public function getRoomUsersDeprecated(Request $request)
    {
        $uid       = $request->owner_id;
        $room = Room::query()->where(['uid' => $uid])->select('id', 'room_admin')->first();
        if (!$room) {
            return Common::apiResponse(0, 'Room not found', null, 404);
        }

        $roomAdmin = explode(',', $room->room_admin ?? '');
        $admins    = User::with('profile')->whereIn('id', $roomAdmin)->get();
        $admins    = $admins->filter(function ($q) {
            return !Common::hasInPack($q->id, 17, true);
        });
        $admin     = [];
        foreach ($admins as $k => $v) {
            $admin[$k]['id']       = @$v->id;
            $admin[$k]['nickname'] = @$v->nickname;
            $admin[$k]['avatar']   = @$v->profile->avatar;
            $admin[$k]['country']  = @$v->profile->country;
            $admin[$k]['is_admin'] = 1;
        }

        // Use repository for visitor operations
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $roomVisitor = $visitorRepo->getVisitorIds($room->id)->toArray();

        $roomVisitor = array_values(array_diff($roomVisitor, $roomAdmin));
        $visitors    = User::query()->with('profile')->whereIn('id', $roomVisitor)->get();
        $visitors    = $visitors->filter(function ($q) {
            return !Common::hasInPack($q->id, 17, true);
        });
        $visitor     = [];
        foreach ($visitors as $k => $v) {
            $visitor[$k]['id']       = @$v->id;
            $visitor[$k]['nickname'] = @$v->nickname;
            $visitor[$k]['avatar']   = @$v->profile->avatar;
            $visitor[$k]['country']  = @$v->profile->country;
            $visitor[$k]['is_admin'] = 0;
        }
        $res['room_id']  = $uid;
        $res['owner']    = new UserResource(User::with('profile')->find($uid));
        $res['admin']    = UserResource::collection($admins);  //$admin;
        $res['visitors'] = UserResource::collection($visitors); //$visitor;
        return Common::apiResponse(1, '', $res);
    }


    //exit the room

    public function microphone_status(Request $request)
    {
        $uid = $request->owner_id;
        if (!$uid) return Common::apiResponse(0, __('missing owner_id'), null, 422);
        $room =
            (array)DB::table('rooms')->selectRaw("uid,microphone,is_prohibit_sound,room_sound,play_num")->where('uid', $uid)->first();
        if (!$room) return Common::apiResponse(0, __('room not found'), null, 404);
        $microphone        = explode(',', $room['microphone']);
        $is_prohibit_sound = explode(',', $room['is_prohibit_sound']);
        $roomSound_arr     = explode(",", $room['room_sound']);
        $mic               = [];

        // Pre-load all mic users with their profiles in ONE query (fixes N+1)
        $micUserIds = array_filter($microphone, fn($v) => $v > 0);
        $micUsers = [];
        if (!empty($micUserIds)) {
            $micUsers = User::with('profile')
                ->whereIn('id', $micUserIds)
                ->get()
                ->keyBy('id');
        }

        // Pre-load wares for dress_1 and dress_4 values
        $dressIds = $micUsers->pluck('dress_4')->merge($micUsers->pluck('dress_1'))->filter()->unique()->toArray();
        $waresData = [];
        if (!empty($dressIds)) {
            $waresData = DB::table('wares')->whereIn('id', $dressIds)->get(['id', 'color', 'img1'])->keyBy('id');
        }

        foreach ($microphone as $k => &$v) {
            $ar = [];
            foreach ($is_prohibit_sound as $ke => &$va) {
                if ($k == $ke) {
                    $ar['can_lock'] = $va ? 2 : 1;
                }
            }

            if ($v == 0) {
                $ar['status'] = 1;
            } elseif ($v == -1) {
                $ar['status'] = 3;
            } else {
                $ar['status']   = 2;
                $userModel = $micUsers->get($v);
                $ar['user_id']  = $v;
                $ar['avatar']   = $userModel?->profile?->avatar ?? '';
                $ar['nickname'] = $userModel?->nickname ?? '';
                $ar['gender']   = $userModel?->profile?->gender ?? '';
                if ($userModel && $userModel->dress_1) {
                    $ar['txk'] = isset($waresData[$userModel->dress_4]) ? $waresData[$userModel->dress_4]->img1 : '';
                } else {
                    $ar['txk'] = '';
                }
                if ($userModel && $userModel->dress_4) {
                    $ar['mic_color'] = isset($waresData[$userModel->dress_4]) ? ($waresData[$userModel->dress_4]->color ?: '#ffffff') : '#ffffff';
                } else {
                    $ar['mic_color'] = '#ffffff';
                }

                //numerical play
                $ar['is_play'] = $room['play_num'];
                if ($room['play_num']) {
                    $ar['price'] =
                        DB::table('play_num_logs')->where(['uid' => $uid, 'user_id' => $v])->value('price') ?: 0;
                } else {
                    $ar['price'] = 0;
                }
                $ar['is_master'] = $uid == $v ? 1 : 0;

                //countdown time
                $info = (array)Db::table('time_logs')->selectRaw('created_at,time')->where([
                    'uid'  => $uid,
                    'muid' => $v
                ])->orderByRaw('id desc')->limit(1)->first();
                if (!empty($info) && $info['time'] && $info['created_at']) {
                    $endTime          = ($info['time'] + $info['created_at']);
                    $remainTime       = ($endTime - time());
                    $ar['remainTime'] = $remainTime <= 0 ? 0 : (string)$remainTime;
                    if ($ar['remainTime'] <= 0) {
                        Db::table('time_logs')->where(['uid' => $uid, 'muid' => $v])->delete();
                    }
                }
            }
            $ar['is_muted'] = in_array($v, $roomSound_arr) ? 2 : 1;
            $mic[]          = $ar;
        }
        $wait_user_id      = DB::table('mics')->where([
            'roomowner_id' => $uid,
            'type' => 1
        ])->orderBy('id', 'asc')->limit(1)->value('user_id');
        $arr['user_id']    = !$wait_user_id ? '' : $wait_user_id;
        $arr['microphone'] = $mic;
        return Common::apiResponse(1, '', $arr);
    }

    public function up_microphone(Request $request)
    {
        $data    = $request;
        $user_id = $request->user_id;
        $phase   = $request->phase;
        if (!$data['owner_id'] || !$user_id) return Common::apiResponse(0, __('Missing data'), null, 422);

        $room = Room::query()->where(['uid' => $data['owner_id']])->select('id', 'room_admin', 'microphone', 'free_mic', 'mode')->first();
        if (!$room) return Common::apiResponse(0, __('room does not exist'));

        // Security check: validate user is in room (unless they are the owner)
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        if (!$visitorRepo->isVisitor($room->id, $user_id) && $data['owner_id'] != $user_id) {
            return Common::apiResponse(0, __('The user is not in this room'), null, 403);
        }

        $room = $room->toArray();

        $position = $data['position']; //mic sequence 0-8
        if ($room['mode'] != '1') {
            if ($position < 0 || $position > 9) return Common::apiResponse(0, __('position error'), null, 422);
        } else {
            if ($position < 0 || $position > 17) return Common::apiResponse(0, __('position error'), null, 422);
        }
        $mic_arr = explode(',', $room['microphone']);
        if (@$mic_arr[$position] == -1) return Common::apiResponse(0, __('This slot has been locked'), null, 408);
        if (@$mic_arr[$position] != 0) return Common::apiResponse(0, __('There is a user on the mic'), null, 405);


        //How to play free mic
        $adm_id = $request->user()->id;
        if ($room['free_mic'] == 1 && $adm_id != $data['owner_id']) {
            $adm_arr = $room['room_admin'] ? explode(",", $room['room_admin']) : [$data['owner_id']];

            // FIX: Check if admin is in room using repository
            if (!$visitorRepo->isVisitor($room['id'], $adm_id)) {
                return Common::apiResponse(0, __('Please enter this room first'), null, 403);
            }

            if (!in_array($adm_id, $adm_arr)) return Common::apiResponse(0, __('You do not have this permission yet'), null, 408);
        }


        //If it is on the mic, skip to the top mic, and the original mic is empty
        if (in_array($user_id, $mic_arr)) {
            $key           = array_search($user_id, $mic_arr);
            $mic_arr[$key] = 0;
        }

        $arr = $mic_arr;


        if ($phase < 4) $arr[] = $data['owner_id'];
        // $cp_arr = [];
        // foreach ($arr as $k => &$v) {
        //     if ($v == -1 || $v == 0) continue;
        //     $cp_id = Common::check_first_cp($user_id, $v, 1);
        //     if ($cp_id) {
        //         $level            = Common::getLevel($v, 3);
        //         $ar['cp_level']   = Common::getCpLevel($cp_id);
        //         $ar['nick_color'] = Common::getNickColorByVip($level);
        //         $ar['id']         = $v;
        //         $ar['nickname']   = DB::table('users')->where(['id' => $v])->value('nickname');
        //         $ar['exp']        = DB::table('cp')->where(['id' => $cp_id])->value('exp');
        //         $img              = @User::query()->find($v)->profile->avatar;
        //         $ar['img']        = $img;
        //         $cp_arr[]         = $ar;
        //     }
        // }
        // if ($cp_arr) {
        //     array_multisort(array_column($cp_arr, 'exp'), SORT_DESC, $cp_arr);
        // }
        // $cp_xssm = Common::getConf('cp_xssm');
        // $i       = 0;
        // foreach ($cp_arr as $k => &$va) {
        //     if (!$i) {
        //         $va['cp_xssm'] = $va['cp_level'] >= 7 ? $cp_xssm : '';
        //     } else {
        //         $va['cp_xssm'] = '';
        //     }
        //     $i++;
        // }
        if (@$mic_arr[$position]) {
            $mic_arr[$position] = $user_id;
        }
        $mic  = implode(',', $mic_arr);
        $res  = DB::table('rooms')->where('uid', $data['owner_id'])->update(['microphone' => $mic]);
        $room = Room::query()->where('uid', $data['owner_id'])->first();
        $pk   = Pk::query()->where('room_id', $room->id)->where('status', 1)->first();
        if ($pk) {
            $pk->mics = $mic;
            $pk->save();
        }

        $user               = (array)DB::table('users')->selectRaw('id,nickname')->find($user_id);
        $u                  = User::query()->with('profile')->find($user_id);
        $user['avatar']     = @$u->profile->avatar;
        $user_level         = Common::getLevel($user_id, 3);
        $user['nick_color'] = Common::getNickColorByVip($user_level);
        // $res_arr['cp']      = $cp_arr;
        $res_arr['user']    = $user;

        if ($res) {

            //Remove mic sequence
            Common::delMicHand($user_id);
            LiveTime::query()->where('uid', $user_id)->where('end_time', null)->whereDate('created_at', '!=', today())->delete();
            $t =
                LiveTime::query()->where('uid', $user_id)->where('end_time', null)->whereDate('created_at', today())->orderByDesc('id')->first();
            if ($t) {
                LiveTime::query()->where('uid', $user_id)->where('end_time', null)->where('id', '!=', $t->id)->delete();
            }

            if (!$t) {
                LiveTime::query()->create([
                    'uid' => $user_id,
                    'start_time' => time()
                ]);
            }

            $ms   = [
                'messageContent' => [
                    'message' => 'upMic',
                    'userId' => $user_id,
                    'position' => $position,
                    'userName' => @$u->name
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $user_id, $json);
            return Common::apiResponse(1, __('Success on the mic'), $res_arr);
        } else {
            return Common::apiResponse(0, __('Failed to mic'), null, 400);
        }
    }


    //getRoomUsers

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update0(EditRoomRequest $request, $id)
    {

        try {
            $this->roomService->update($request, $id);
            return $this->enter_room($request);
        } catch (Exception $exception) {
            return Common::apiResponse(false, 'failed', $exception, 400);
        }
    }


    // mic sequence list

    public function enter_room(Request $request)
    {
        $room_pass = $request['room_pass'];
        $owner_id  = $request['owner_id'];

        if ($request->type == 'random') {
            $owner_id = Room::query()->where('room_status', 1)->where('uid', '!=', null)->where(function ($q) {
                $q->where('count_room_socket', '!=', 0)->orWhere('is_afk', 1);
            })->pluck('uid')->random();
        }

        $user    = $request->user();
        $user_id = $user->id;

        // if owner id not path throw error
        if (!$owner_id) return Common::apiResponse(0, 'not found', null, 404);

        //check if this user in black-list
        $black_list = Common::getUserBlackList($owner_id);
        if (in_array($user_id, $black_list)) return Common::apiResponse(false, __('You have been blocked by the other party'), null, 423);

        // get room by owner_id
        $room = Room::query()->where('uid', $owner_id)->with(['owner', 'roomCategory', 'family'])->first();

        if (!$room) return Common::apiResponse(false, 'No room yet, please create first', null, 404);
        // (The authoritative room-ban/kick gate lives in
        // EnteranceRoomServices::checkRoomBanOrKick — used by both the audio and
        // live entry paths — so it is not duplicated here.)

        if ($room->room_pass && $owner_id != $user_id && !$request->ignorePassword) {
            if (!$room_pass) return Common::apiResponse(false, __('The room is locked, please enter the password'), null, 409);
            if ($room->room_pass != $room_pass) return Common::apiResponse(false, __('Password is incorrect, please re-enter'), null, 410);
        }


        if (!$request->is_update) {
            // Wire param from the app — keep the legacy name until a coordinated
            // app+backend rename (same class as the zego_feature settings key).
            if ($request->sendToZego != 'no') {
                dispatch(new EnterRoomZigoRequest($user, $room->id, $request->have_vip));
            }
        }
        //        $this->getRoomTwoLastPk($room->id);
        $room_info = (new EnterRoomCollection($room, $user_id));

        $this->enterTheRoomCreateOrUpdate($user_id, $owner_id, $room->id);

        //send to stream
        $user->enableSaving = false;
        $user->now_room_uid = (int)$owner_id;
        $user->save();


        // Replace this line with your existing return statement
        return Common::apiResponse(true, '', $room_info);
    }


    // on the mic

    private function enterTheRoomCreateOrUpdate($user_id, $owner_id, $room_id)
    {
        EnteredRoom::query()->updateOrCreate([
            'uid' => $user_id,
            'ruid' => $owner_id,
            'rid' => $room_id
        ], [
            'entered_at' => now()
        ]);
    }

    //leave mic

    public function go_microphone(Request $request)
    {
        $data   = $request;
        $result = Common::go_microphone_hand($data['owner_id'], $data['user_id']);
        $room   = Room::query()->where('uid', $data['owner_id'])->first();
        if (!$room) return Common::apiResponse(0, 'room not found', null, 404);
        if ($result) {
            $this->calcTime($data['user_id']);
            //            $ms = [
            //                "messageContent"=>[
            //                    "message"=>"leaveMic",
            //                    "userId"=>$data['user_id']
            //                ]
            //            ];
            //            $json = json_encode ($ms);
            //            Common::sendToStream ('SendCustomCommand',$room->id,$data['user_id'],$json);
            return Common::apiResponse(1, __('Success'));
        } else {
            return Common::apiResponse(0, __('Failed'), null, 400);
        }
    }


    //mute mic place
    public function mute_microphone(Request $request)
    {
        $data     = $request;
        $position = $data['position'];
        $room     = Room::query()->where('uid', $data['owner_id'])->first();
        if (@$room->mode != '1') {
            if ($position < 0 || $position > 9) return Common::apiResponse(0, __('position error'), null, 422);
        } else {
            if ($position < 0 || $position > 17) return Common::apiResponse(0, __('position error'), null, 422);
        }
        $admins = Room::query()->where('uid', $data['owner_id'])->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $data['owner_id'] && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'), null, 408);
        }

        $microphone = DB::table('rooms')->where('uid', $data['owner_id'])->value('microphone');
        $microphone = explode(',', $microphone);
        if (@$microphone[$position]) {
            $microphone[$position] = -2;
        }
        $microphone = implode(',', $microphone);
        $res        = DB::table('rooms')->where('uid', $data['owner_id'])->update(['microphone' => $microphone]);
        if (true) {
            $ms   = [
                'messageContent' => [
                    'message' => 'muteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('Successfully locked the microphone position'));
        } else {
            return Common::apiResponse(0, __('Failed to lock microphone'), null, 400);
        }
    }

    //unmute mic place
    public function unmute_microphone(Request $request)
    {
        $data     = $request;
        $position = $data['position'];
        $room     = Room::query()->where('uid', $data['owner_id'])->first();
        if (@$room->mode != '1') {
            if ($position < 0 || $position > 9) return Common::apiResponse(0, __('position error'), null, 422);
        } else {
            if ($position < 0 || $position > 17) return Common::apiResponse(0, __('position error'), null, 422);
        }
        $admins = Room::query()->where('uid', $data['owner_id'])->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $data['owner_id'] && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'), null, 408);
        }
        $microphone = DB::table('rooms')->where('uid', $data['owner_id'])->value('microphone');
        $microphone = explode(',', $microphone);
        if (@$microphone[$position]) {
            $microphone[$position] = 0;
        }
        $microphone = implode(',', $microphone);
        $res        = DB::table('rooms')->where('uid', $data['owner_id'])->update(['microphone' => $microphone]);
        if (true) {
            $room = Room::query()->where('uid', $data['owner_id'])->first();
            $ms   = [
                'messageContent' => [
                    'message' => 'unmuteMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('Successfully unlocked the microphone'));
        } else {
            return Common::apiResponse(0, __('Failed to unlock microphone'), null, 400);
        }
    }

    //lock mic place
    public function shut_microphone(Request $request)
    {
        $data     = $request;
        $position = $data['position'];
        $room     = Room::query()->where('uid', $data['owner_id'])->first();
        if (@$room->mode != '1') {
            if ($position < 0 || $position > 9) return Common::apiResponse(0, __('position error'), null, 422);
        } else {
            if ($position < 0 || $position > 17) return Common::apiResponse(0, __('position error'), null, 422);
        }
        $admins = Room::query()->where('uid', $data['owner_id'])->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $data['owner_id'] && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'), null, 408);
        }

        $microphone = DB::table('rooms')->where('uid', $data['owner_id'])->value('microphone');
        $microphone = explode(',', $microphone);
        if (@$microphone[$position] == false) {
            $microphone[$position] = -1;
        }
        $microphone = implode(',', $microphone);
        $res        = DB::table('rooms')->where('uid', $data['owner_id'])->update(['microphone' => $microphone]);
        if ($res) {
            $ms   = [
                'messageContent' => [
                    'message' => 'lockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('Successfully locked the microphone position'));
        } else {
            return Common::apiResponse(0, __('Failed to lock microphone'), null, 400);
        }
    }


    //open mic place
    public function open_microphone(Request $request)
    {
        $data     = $request;
        $position = $data['position'];
        $room     = Room::query()->where('uid', $data['owner_id'])->first();
        if (@$room->mode != '1') {
            if ($position < 0 || $position > 9) return Common::apiResponse(0, __('position error'), null, 422);
        } else {
            if ($position < 0 || $position > 17) return Common::apiResponse(0, __('position error'), null, 422);
        }
        $admins = Room::query()->where('uid', $data['owner_id'])->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $data['owner_id'] && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'), null, 408);
        }
        $microphone = DB::table('rooms')->where('uid', $data['owner_id'])->value('microphone');
        $microphone = explode(',', $microphone);
        if (@$microphone[$position]) {
            $microphone[$position] = 0;
        }
        $microphone = implode(',', $microphone);
        $res        = DB::table('rooms')->where('uid', $data['owner_id'])->update(['microphone' => $microphone]);
        if (true) {
            $room = Room::query()->where('uid', $data['owner_id'])->first();
            $ms   = [
                'messageContent' => [
                    'message' => 'unLockMic',
                    'userId' => $request->user()->id,
                    'position' => $data['position']
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            return Common::apiResponse(1, __('Successfully unlocked the microphone'));
        } else {
            return Common::apiResponse(0, __('Failed to unlock microphone'), null, 400);
        }
    }


    //Turn off user microphone
    public function is_sound(Request $request)
    {
        $user_id = $request->user_id ?: 0;
        $uid     = $request->owner_id ?: 0;
        if (!$uid || !$user_id) return Common::apiResponse(0, __('require user_id and owner_id'), null, 422);
        $admins = Room::query()->where('uid', $uid)->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $uid && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'), null, 408);
        }
        $sound     = DB::table('rooms')->where('uid', $uid)->value('room_sound');
        $sound_arr = explode(',', $sound);
        if (in_array($user_id, $sound_arr)) return Common::apiResponse(0, __('The user is already muted, please do not repeat the settings'), null, 444);

        array_push($sound_arr, $user_id);
        $str = implode(',', $sound_arr);
        $res = DB::table('rooms')->where('uid', $uid)->update(['room_sound' => $str]);
        if ($res) {
            $room = Room::query()->where('uid', $uid)->first();
            $ms   = [
                'messageContent' => [
                    'message' => 'muteMic',
                    'userId' => $user_id,
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $user_id, $json);
            return Common::apiResponse(1, __('Successfully muted'));
        } else {
            return Common::apiResponse(0, __('Failed to mute'), null, 400);
        }
    }

    //Open user voice microphone
    public function remove_sound(Request $request)
    {
        $user_id = $request->user_id ?: 0;
        $uid     = $request->owner_id ?: 0;
        if (!$uid || !$user_id) return Common::apiResponse(0, __('require user_id and owner_id'), null, 422);
        $admins = Room::query()->where('uid', $uid)->value('room_admin');
        $admins = explode(',', $admins);
        if ($request->user()->id != $uid && !in_array($request->user()->id, $admins)) {
            return Common::apiResponse(0, __('you dont have permission'));
        }
        $sound     = DB::table('rooms')->where('uid', $uid)->value('room_sound');
        $sound_arr = explode(',', $sound);
        if (!in_array($user_id, $sound_arr)) return Common::apiResponse(0, __('The user is no longer in the ban list, please do not repeat the settings'), null, 444);
        $key = array_search($user_id, $sound_arr);
        unset($sound_arr[$key]);
        $sound = implode(',', $sound_arr);
        $res   = DB::table('rooms')->where('uid', $uid)->update(['room_sound' => $sound]);
        if ($res) {
            $room = Room::query()->where('uid', $uid)->first();
            $ms   = [
                'messageContent' => [
                    'message' => 'UnMuteMic',
                    'userId' => $user_id,
                ]
            ];
            $json = json_encode($ms);
            Common::sendToStream('SendCustomCommand', $room->id, $user_id, $json);
            return Common::apiResponse(1, __('Successfully unmuted'));
        } else {
            return Common::apiResponse(0, __('Unmute failed'), null, 400);
        }
    }

    //kick out of the room
    public function out_room(Request $request)
    {
        $uid      = $request->owner_id ?: 0;
        $roomId = $request->room_id;
        $black_id = (int) ($request->user_id ?: 0);
        $duration = $request->minutes ?: 5;
        // Two distinct moderation actions share this endpoint:
        //   - 'kick' (طرد): a TIMED removal ($duration minutes). The room stays
        //     visible in the target's home; they can re-enter when it expires.
        //   - 'ban'  (حظر): a PERMANENT ban. The room is hidden from the target's
        //     home and they cannot re-enter until an admin/owner unbans them.
        // Back-compat: older app builds signalled permanent with minutes=525600.
        $type = $request->type === 'ban' ? 'ban' : 'kick';
        if ($type === 'kick' && $duration >= 525600) $type = 'ban';

        if ((!$uid && !$roomId) || !$black_id) return Common::apiResponse(0, 'invalid data', null, 422);
        if (Common::pack_get(9, $black_id)) return Common::apiResponse(0, 'cant kick this user', null, 422);
        //        if (!Common::can_kick ($black_id)) return Common::apiResponse (0,'cant kick this user',null,403);
        $room = $roomId
            ? Room::find($roomId)
            : Room::where('uid', $uid)->where('type', 'audio')->first();
        if (!$room) return Common::apiResponse(0, 'room not found', null, 422);
        $uid = $room->uid;
        $room_id = @$room->id;

        // Authorization: owner can always act; an admin needs the matching granular
        // power (ban_users for a permanent ban, kick_from_room for a timed kick).
        // can() falls back to full powers for legacy CSV admins, so older rooms are
        // unaffected — only granular-restricted admins are now actually enforced.
        $requiredPower = $type === 'ban' ? 'ban_users' : 'kick_from_room';
        if (!\App\Helpers\RoomHelper::can((int) $room->id, $requiredPower)) {
            return Common::apiResponse(0, 'You do not have permission to perform this action', null, 403);
        }

        // The owner can never be kicked/banned from their own room.
        if ($black_id == $room->uid) {
            return Common::apiResponse(0, 'cant kick this user', null, 422);
        }

        // Use new RoomBlacklistRepository with dual-write
        $blacklistRepo = app(\App\Repositories\RoomBlacklistRepository::class);

        if ($type === 'ban') {
            // Permanent ban (no expiry) — strongest action, always overrides any
            // existing timed kick.
            $blacklistRepo->removeBan($room->id, $black_id);
            $result = $blacklistRepo->addBan($room->id, $black_id, Auth::id(), null, 'Permanently banned');
        } else {
            $durationSeconds = $duration * 60; // Convert minutes to seconds
            // A timed kick must NEVER shorten an existing stronger ban: if the target
            // is already permanently banned or under a longer timed kick, keep it.
            if ($blacklistRepo->isBlacklisted($room->id, $black_id)) {
                $isPermanent = $blacklistRepo->isPermanentBan($room->id, $black_id);
                $remaining   = (int) $blacklistRepo->getTimeRemaining($room->id, $black_id);
                if (!$isPermanent && $durationSeconds > $remaining) {
                    $blacklistRepo->removeBan($room->id, $black_id);
                    $result = $blacklistRepo->addBan($room->id, $black_id, Auth::id(), $durationSeconds, 'Kicked out for ' . $duration . ' minutes');
                } else {
                    $result = true; // existing stronger ban preserved
                }
            } else {
                $result = $blacklistRepo->addBan($room->id, $black_id, Auth::id(), $durationSeconds, 'Kicked out for ' . $duration . ' minutes');
            }
        }

        if ($result) {
            //exit the room
            Common::quit_hand_2($uid, $black_id);
            // 🔴 quit_hand_2 computes the new visitor list but does NOT persist the
            // removal, and the old code never force-disconnected the participant —
            // so a kicked user lingered (visible) until the ~45s occupancy resync.
            // Persist the visitor removal + force-disconnect from the UTD-Stream
            // engine so the kick takes effect immediately.
            try { app(\App\Repositories\RoomVisitorRepository::class)->removeVisitor($room->id, $black_id); } catch (\Throwable $e) {}
            try { Common::kickUser((string) $room->id, (string) $black_id); } catch (\Throwable $e) {}
            // Load user once instead of twice (fixes duplicate User::find N+1)
            $b = User::find($black_id);
            if ($b) {
                $b->now_room_uid = 0;
                $b->save();
            }
            $mc   = [
                'messageContent' => [
                    'message' => 'kickout',
                    'type' => $type,
                    // Target id so ONLY the kicked client acts on this frame. The
                    // realtime targeted-send can fan out beyond the target, so the
                    // app self-checks this against its own id (otherwise the owner
                    // kicked themselves when kicking someone).
                    'user_id' => $black_id,
                    'duration' => $type === 'ban' ? 0 : $duration
                ]
            ];
            $json = json_encode($mc);
            $n    = $b ? ($b->name ?: 'nan') : 'nan';

            // record kicked user
            $user_request = $request->user();
            KickRecord::create([
                "kicked_user_id" => $user_request->id,
                "user_id" => $black_id,
                "room_id" => $room_id,
            ]);

            Common::sendToStream_4('SendCustomCommand', $room_id, $uid, $black_id, $json);
            $this->calcTime($black_id);
            $message = $type === 'ban'
                ? __('api.permanentBanRoom', ['name' => $b->name ?? 'nan', 'actionName' => $request->user()->name], 'ar')
                : __('api.blockRoom', ['name' => $b->name ?? 'nan', 'actionName' => $request->user()->name, 'duration' => $duration], 'ar');

            Common::sendToStream_2('SendBroadcastMessage', $room_id, $uid, 'room', $message);
            return Common::apiResponse(1, 'success');
        } else {
            return Common::apiResponse(0, 'fail', null, 400);
        }
    }

    //make favorite room
    public function room_mykeep(Request $request)
    {
        $data        = $request;
        $uid         = $data['owner_id'];
        $user_id     = $request->user()->id;
        $mykeep_list = DB::table('users')->where('id', $user_id)->value('mykeep');
        $mykeep_arr  = explode(",", $mykeep_list);
        if (in_array($uid, $mykeep_arr)) return Common::apiResponse(0, 'Do not repeat favorites', null, 444);

        array_unshift($mykeep_arr, $uid);
        $str = trim(implode(",", $mykeep_arr), ",");
        $res = DB::table('users')->where('id', $user_id)->update(['mykeep' => $str]);
        if ($res) {
            return Common::apiResponse(1, 'success');
        } else {
            return Common::apiResponse(0, 'failed', null, 400);
        }
    }


    //cancel favorite room
    public function remove_mykeep(Request $request)
    {
        $data        = $request;
        $uid         = $data['owner_id'];
        $user_id     = $request->user()->id;
        $mykeep_list = DB::table('users')->where('id', $user_id)->value('mykeep');
        $mykeep_arr  = explode(",", $mykeep_list);
        if (!in_array($uid, $mykeep_arr)) return Common::apiResponse(0, 'This room has not been favorited', null, 404);
        $key = array_search($uid, $mykeep_arr);
        unset($mykeep_arr[$key]);
        $str = trim(implode(",", $mykeep_arr), ",");
        $res = DB::table('users')->where('id', $user_id)->update(['mykeep' => $str]);
        if ($res) {
            return Common::apiResponse(1, 'success');
        } else {
            return Common::apiResponse(0, 'failed', null, 400);
        }
    }


    //Whether to set a password
    public function is_pass(Request $request)
    {
        $uid = $request->owner_id ?: 0;
        if (!$uid) return Common::apiResponse(0, 'invalid data');
        $result = DB::table('rooms')->where('uid', $uid)->value('room_pass');
        if ($result) {
            return Common::apiResponse(1, 'The room has a password, please enter the password', ['is_password' => true]);
        } else {
            return Common::apiResponse(1, 'room without password', ['is_password' => false]);
        }
    }

    //Get other users in the room
    public function get_other_user(Request $request)
    {
        $data    = $request;
        $uid     = $data['owner_id'];
        $user_id = $data['user_id'];
        $my_id   = $request->user()->id;

        $room_info                 = DB::table('rooms')->where('uid', $uid)->select([
            'room_admin',
            'room_speak',
            'room_judge',
            'room_sound'
        ])->get()->toArray();
        $room_info[0]              = (array)$room_info[0];
        $room_info[0]['user_type'] = 5;
        $roomAdmin                 = explode(',', $room_info[0]['room_admin']);
        for ($i = 0; $i < count($roomAdmin); $i++) {
            if ($roomAdmin[$i] == $user_id) {
                $room_info[0]['user_type'] = 2;
            }
        }
        $roomJudge = explode(',', $room_info[0]['room_judge']);
        for ($i = 0; $i < count($roomJudge); $i++) {
            if ($roomJudge[$i] == $user_id) {
                $room_info[0]['user_type'] = 4;
            }
        }
        $room_info[0]['is_speak'] = 1;
        $is_speak                 = explode(',', $room_info[0]['room_speak']);
        for ($i = 0; $i < count($is_speak); $i++) {
            if ($is_speak[$i] == $user_id) {
                $room_info[0]['is_speak'] = 2;
            }
        }
        // $room_info[0]['is_sound'] = 1;
        // $is_sound = explode(',', $room_info[0]['roomSound']);
        // for ($i=0; $i < count($is_sound); $i++) {
        //     if($is_sound[$i] == $user_id){
        //         $room_info[0]['is_sound'] = 2;
        //     }
        // }

        $is_sound_arr             = $room_info[0]['room_sound'] ? explode(',', $room_info[0]['room_sound']) : [];
        $room_info[0]['is_sound'] = in_array($user_id, $is_sound_arr) ? 2 : 1;


        $result = DB::table('users')->where('id', $user_id)->select(['id', 'nickname'])->get()->toArray();

        $result[0] = (array)$result[0];

        $is_follows = Common::IsFollow($my_id, $user_id);

        $result[0]['is_follows'] = $is_follows ? 1 : 2;

        $user = User::with('profile')->find($result[0]['id']);

        $result[0]['image'] = @$user->profile->avatar;
        $result[0]['age']   = Common::getBrithdayMsg(@$user->profile->birthday, 0) ?: 0;

        $result[0]['user_type'] = $room_info[0]['user_type'];
        $result[0]['is_speak']  = $room_info[0]['is_speak'];
        $result[0]['is_sound']  = $room_info[0]['is_sound'];


        $star_level            = Common::getLevel($user_id, 1);
        $gold_level            = Common::getLevel($user_id, 2);
        $vip_level             = Common::getLevel($user_id, 3);
        $vipImages = DB::table('vips')
            ->where(function ($q) use ($star_level, $gold_level, $vip_level) {
                $q->where(fn($q) => $q->where('level', $star_level)->where('type', 1))
                  ->orWhere(fn($q) => $q->where('level', $gold_level)->where('type', 2))
                  ->orWhere(fn($q) => $q->where('level', $vip_level)->where('type', 3));
            })
            ->get(['level', 'type', 'img'])
            ->keyBy('type');
        $result[0]['star_img'] = $vipImages->get(1)?->img;
        $result[0]['gold_img'] = $vipImages->get(2)?->img;
        $result[0]['vip_img']  = $vipImages->get(3)?->img;

        $result[0]['is_time'] = 0;
        $info                 = Db::table('time_logs')->selectRaw('created_at,time')->where([
            'uid'     => $uid,
            'user_id' => $result[0]['id']
        ])->orderByRaw('id desc')->limit(1)->first();

        if (!empty($info) && $info['time'] && $info['created_at']) {
            $endTime              = ($info['time'] + $info['created_at']);
            $remainTime           = ($endTime - time());
            $result[0]['is_time'] = $remainTime < 0 ? 0 : 1;
            //delete timer
            if ($remainTime < 0) {
                Db::table('time_logs')->where(['uid' => $uid, 'user_id' => $result[0]['id']])->delete();
            }
        }


        if ($result) {
            return Common::apiResponse(1, 'success', $result);
        } else {
            return Common::apiResponse(0, 'failed', null, 400);
        }
    }


    // //can you speak
    // public function not_speak_status()
    // {
    //     $uid     = input('uid/d', 0);
    //     $user_id = $this->user_id;
    //     if (!$uid) $this->ApiReturn(0, '缺少参数');
    //     $roomSpeak = DB::name('rooms')->where('uid', $uid)->value('roomSpeak');
    //     $spe_arr   = !$roomSpeak ? [] : explode(',', $roomSpeak);

    //     $is_speak = 1;
    //     foreach ($spe_arr as $k => &$v) {
    //         $arr      = explode("#", $v);
    //         $new_time = $arr[1] + 180;
    //         if (time() - $new_time < 0) {
    //             if ($arr[0] == $user_id) {
    //                 $is_speak = 0;
    //             }
    //         } else {
    //             unset($spe_arr[$k]);
    //         }
    //     }
    //     $str = trim(implode(",", $spe_arr), ",");
    //     DB::name('rooms')->where(['uid' => $uid])->update(['roomSpeak' => $str]);

    //     if ($is_speak) {
    //         $this->ApiReturn(1, '可以发言');
    //     } else {
    //         $this->ApiReturn(0, '不能发言');
    //     }
    // }




    public function room_type()
    {
        $data = DB::table('room_categories')->where(['pid' => 0, 'enable' => 1])->selectRaw("id,name")->get();
        return Common::apiResponse(1, '', $data);
    }


    //set as admin
    public function is_admin(Request $request)
    {
        $uid      = $request->owner_id;
        $admin_id = $request->user_id;
        $roomId = $request->room_id;
        if ((!$uid || !$roomId) && !$admin_id) return Common::apiResponse(0, 'invalid data', null, 422);

        $room = $roomId
            ? Room::find($roomId)
            : Room::where('uid', $uid)->where('type', 'audio')->first();

        if (!$room) return Common::apiResponse(0, 'Room not exist', null, 422);
        $uid  = $room->uid;
        // Only the room owner may add an admin (matches remove_admin /
        // update_admin_permissions which already enforce this). Without this gate
        // ANY authenticated user could add an admin to ANY room.
        if ($request->user()->id != $room->uid) {
            return Common::apiResponse(0, 'you do not have permission', null, 403);
        }
        if ($room->uid == $admin_id) return Common::apiResponse(0, 'invalid data', null, 422);
        // Audio rooms keep the strict presence check (room_visitors is written
        // synchronously on entry). LIVE rooms' visitor rows are reconciled from
        // the UTD Stream webhooks (eventually consistent) — webhook lag must
        // not block persisting an admin the owner can SEE in the broadcast.
        if ($room->type === 'audio' && !$room->roomVisitors()->where('user_id', $admin_id)->exists()) {
            return Common::apiResponse(0, 'This user is not in this room', null, 404);
        }

        $adminRepo = app(\App\Repositories\RoomAdministratorRepository::class);
        $roomMax   = $room->total_admins;
        $configMaxRoom = Common::getConfig('max_room_admin') ?? 4;
        $maxAdmins = max($roomMax, $configMaxRoom);

        // Check if user is already an admin
        if ($adminRepo->isAdmin($room->id, $admin_id)) {
            return Common::apiResponse(0, 'This user is already an administrator, please do not repeat the settings', null, 444);
        }

        // Check if room is full
        if (!$adminRepo->canAddAdmin($room->id, $maxAdmins)) {
            return Common::apiResponse(0, 'room manager is full', null, 404);
        }

        // Add admin using repository (dual-write to both new table and legacy column).
        // Optional `permissions` array restricts the admin to those powers;
        // omitted/null = all powers (legacy behaviour).
        $permissions = is_array($request->permissions)
            ? array_values(array_filter($request->permissions, 'is_string'))
            : null;
        $res = $adminRepo->addAdmin($room->id, $admin_id, $request->user()->id ?? null, $permissions);

        if ($res) {
            // Get current admins for stream notification
            $adm_arr = $adminRepo->getAdmins($room->id)->toArray();

            $a = User::find($admin_id);
            $n = $a ? ($a->name ?: 'nan') : 'nan';

            // Send stream notification
            $ms = [
                'messageContent' => [
                    'message' => 'updateAdmins',
                    'admins' => array_values($adm_arr),
                    // carry per-admin permissions so clients enforce granular powers
                    // live (membership alone left restricted admins with ALL powers
                    // until a rejoin).
                    'admin_permissions' => $adminRepo->getAdminPermissions($room->id)
                ]
            ];

            Common::sendToStream('SendCustomCommand', $room->id, $uid, json_encode($ms));
            return Common::apiResponse(1, 'Set administrator successfully', $adm_arr, 200);
        } else {
            return Common::apiResponse(0, 'Failed to set administrator', null, 400);
        }
    }

    //cancel manager
    /**
     * Owner-only: edit an existing admin's permission set.
     * Body: room_id, user_id, permissions (array of keys) or null = all.
     */
    public function update_admin_permissions(Request $request)
    {
        $room = Room::find($request->room_id);
        if (!$room) return Common::apiResponse(0, 'Room not exist', null, 422);
        if ($room->uid != $request->user()->id) {
            return Common::apiResponse(0, __('you don not have permission'), null, 403);
        }

        $adminRepo = app(\App\Repositories\RoomAdministratorRepository::class);
        if (!$adminRepo->isAdmin($room->id, (int) $request->user_id)) {
            return Common::apiResponse(0, 'Not an administrator', null, 404);
        }

        $permissions = is_array($request->permissions)
            ? array_values(array_filter($request->permissions, 'is_string'))
            : null;
        $adminRepo->updatePermissions($room->id, (int) $request->user_id, $permissions);

        // Broadcast the updated admin+permissions so every client applies the new
        // power set live (previously this endpoint sent NOTHING — edits only took
        // effect after a full rejoin).
        $ms = [
            'messageContent' => [
                'message' => 'updateAdmins',
                'admins' => array_values($adminRepo->getAdmins($room->id)->toArray()),
                'admin_permissions' => $adminRepo->getAdminPermissions($room->id),
            ],
        ];
        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, json_encode($ms));

        return Common::apiResponse(1, '', [
            'permissions' => $permissions,
        ]);
    }

    public function remove_admin(Request $request)
    {
        $user = $request->user();
        $uid      = $request->owner_id;
        $admin_id = $request->user_id;
        $roomId = $request->room_id;
        if ((!$uid && !$roomId) || !$admin_id) return Common::apiResponse(0, 'invalid data', null, 422);
        $room = $roomId
            ? Room::find($roomId)
            : Room::where('uid', $uid)->where('type', 'audio')->first();
        if (!$room) return Common::apiResponse(0, 'room not found', null, 422);
        $uid      = $room->uid;
        if ($user->id != $room->uid) return Common::apiResponse(0, __('you don not have permission'), null, 404);

        $adminRepo = app(\App\Repositories\RoomAdministratorRepository::class);

        // Check if user is an admin
        if (!$adminRepo->isAdmin($room->id, $admin_id)) {
            return Common::apiResponse(0, 'This user is not an administrator of this room', null, 404);
        }

        // Remove admin using repository (dual-write to both new table and legacy column)
        $res = $adminRepo->removeAdmin($room->id, $admin_id);

        if ($res) {
            // Get current admins for response
            $adm_arr = $adminRepo->getAdmins($room->id)->toArray();

            $a = User::find($admin_id);
            $n = $a ? ($a->name ?: 'nan') : 'nan';

            // Send stream notification
            $ms = [
                'messageContent' => [
                    'message' => 'updateAdmins',
                    'admins' => array_values($adm_arr),
                    // carry per-admin permissions so clients enforce granular powers
                    // live (membership alone left restricted admins with ALL powers
                    // until a rejoin).
                    'admin_permissions' => $adminRepo->getAdminPermissions($room->id)
                ]
            ];

            Common::sendToStream('SendCustomCommand', $room->id, $uid, json_encode($ms));
            return Common::apiResponse(1, 'Cancel administrator successfully', $adm_arr, 200);
        } else {
            return Common::apiResponse(0, 'Failed to cancel administrator', null, 400);
        }
    }

    //add ban
    public function is_black(Request $request)
    {
        $uid     = $request->owner_id;
        $user_id = $request->user_id;

        if (Common::hasInPack($user_id, 15)) {
            return Common::apiResponse(0, 'user cannot banned', null, 403);
        }
        if (!$uid || !$user_id) return Common::apiResponse(0, 'invalid data', null, 422);
        if ($uid == $user_id) return Common::apiResponse(0, 'Illegal operation', null, 403);
        //        if ($request->user ()->id != $uid){
        //            return Common::apiResponse(0,'not allowed');
        //        }
        $room = Room::query()->where('uid', $uid)->first();
        if (!$room) {
            return Common::apiResponse(0, 'Room not found', null, 404);
        }

        // Security check: validate user is in room before banning
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        if (!$visitorRepo->isVisitor($room->id, $user_id)) {
            return Common::apiResponse(0, 'This user is not in this room', null, 404);
        }


        $roomSpeak = DB::table('rooms')->where('uid', $uid)->value('room_speak');
        $spe_arr   = !$roomSpeak ? [] : explode(",", $roomSpeak);
        foreach ($spe_arr as $k => &$v) {
            $arr = explode("#", $v);
            if ($arr[0] == $user_id) return Common::apiResponse(0, 'This user is already on the ban list', null, 405);
        }
        $shic    = time() + 18000;
        $jinyan  = $user_id . "#" . $shic;
        $spe_arr = array_merge($spe_arr, [$jinyan]);
        $str     = implode(",", $spe_arr);
        $res     = DB::table('rooms')->where(['uid' => $uid])->update(['room_speak' => $str]);
        if ($res) {
            $ms = [
                'messageContent' => [
                    'message' => 'banFromWriting',
                    'userId' => $user_id
                ]
            ];
            Common::sendToStream('SendCustomCommand', $room->id, $user_id, json_encode($ms));
            return Common::apiResponse(1, 'Succeeded adding writing ban for');
        } else {
            return Common::apiResponse(0, 'Failed to add writing ban', null, 400);
        }
    }

    public function removeBan(Request $request)
    {
        $uid     = $request->owner_id;
        $user_id = $request->user_id;

        if (!$uid || !$user_id) return Common::apiResponse(0, 'invalid data', null, 422);
        if ($uid == $user_id) return Common::apiResponse(0, 'Illegal operation', null, 403);

        $room = Room::query()->where('uid', $uid)->first();

        $roomSpeak = DB::table('rooms')->where('uid', $uid)->value('room_speak');
        $spe_arr   = !$roomSpeak ? [] : explode(",", $roomSpeak);
        foreach ($spe_arr as $k => &$v) {
            $arr = explode("#", $v);
            if ($arr[0] == $user_id) {
                unset($spe_arr[$k]);
            }
        }
        $str = implode(",", $spe_arr);
        $res = DB::table('rooms')->where(['uid' => $uid])->update(['room_speak' => $str]);
        if ($res) {
            $ms = [
                'messageContent' => [
                    'message' => 'removeBanFromWriting',
                    'userId' => $user_id
                ]
            ];
            Common::sendToStream('SendCustomCommand', $room->id, $user_id, json_encode($ms));
            return Common::apiResponse(1, 'Succeeded remove writing ban for');
        } else {
            return Common::apiResponse(0, 'Failed to remove writing ban', null, 400);
        }
    }

    public function removeRoomPass(Request $request)
    {
        $room = $this->roomService->changePasswordRoom($request->owner_id, $request->room_id);

        $data = [
            "messageContent" => [
                "message" => "changeBackground",
                "imgbackground" => $room->final_room_image ??  '',
                "roomIntro" => $room->room_intro ?? "",
                "roomImg" => $room->room_cover ?? "",
                // "room_type" => @$room->myType->name ?? "",
                "room_type" => app()->getLocale() === 'ar' ? @$room->roomCategory?->name  ?? @$room->roomCategory?->name_en : @$room->roomCategory?->name_en ?? @$room->roomCategory?->name,
                "room_name" => @$room->room_name ?? "",
                "is_locked" => false

            ]
        ];
        $json = json_encode($data);
        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
        return Common::apiResponse(1, 'success');
    }


    public function createPK(Request $request)
    {
        $userId = Auth::id();
        if (!$request->owner_id) return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        $room = Room::query()->where('uid', $request->owner_id)->where('room_status', 1)->with('roomVisitors')->first();
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);

        // FIXED BUG: was using assignment (=) instead of comparison (==)
        // Check if room has visitors before allowing PK (unless user is room owner)
        if ($userId != $room->uid && $room->roomVisitors->isEmpty()) {
            return Common::apiResponse(0, 'room closed', null, 403);
        }
        $ex = Pk::query()->where('room_id', $room->id)->where('status', 1)->exists();
        if ($ex) Pk::query()->where('status', 1)->update(['status' => 0]);
        Pk::query()->create([
            'room_id'  => $room->id,
            'status' => 1,
            'mics' => $room->microphone,
            //                'prize_value'=>$request->prize_value,
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addMinutes($request->minutes),
        ]);
        $mc   = [
            'messageContent' => [
                'message' => 'startPK',
                'PkTime' => $request->minutes
            ]
        ];
        $json = json_encode($mc);
        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
        return Common::apiResponse(1, __('api_responses.created'), null, 201);
    }

    public function closePK(Request $request)
    {

        if (!@$request->owner_id) Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        $room = Room::withoutAppends()->where('uid', $request->owner_id)->select('id')->first();

        $pk = Pk::query()->where('room_id', $room->id)->where('status', 1)->orderByDesc('id')->first();
        if (!$pk) {
            return Common::apiResponse(1, __('api_responses.closed'), null, 201);
        }
        if ($pk->t1_score > $pk->t2_score) {
            $winner = 1;
        } elseif ($pk->t2_score > $pk->t1_score) {
            $winner = 2;
        } else {
            $winner = 0;
        }
        $pk->winner = $winner;
        $pk->status = 0;
        $pk->save();

        $mc   = [
            'messageContent' => [
                'message'            => 'closePk',
                'scoreTeam1' => $pk->t1_score,
                'scoreTeam2' => $pk->t2_score,
                'percentagepk_team1' => $pk->t1_per,
                'percentagepk_team2' => $pk->t2_per,
                'winner_Team' => $winner,
            ]
        ];
        $json = json_encode($mc);
        Common::sendToStream('SendCustomCommand', $pk->room_id, $request->user()->id, $json);
        /*foreach($pks as $pk){
//            Pk::query ()->where ('id',$pk->id)->where ('status',1)->update (['status'=>0]);

        }*/
        return Common::apiResponse(1, __('api_responses.closed'), null, 201);
    }


    public function showPK(Request $request)
    {
        if (!$request->owner_id) return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
        $room = Room::withoutAppends()->where('uid', $request->owner_id)->select('id', 'is_show_pk')->first();
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);

        $room->enableSaving = false;
        $room->update(['is_show_pk' => 1]);
        $mc   = [
            'messageContent' => [
                'message' => 'showPK'
            ]
        ];
        $json = json_encode($mc);
        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
        return Common::apiResponse(1, 'done', null, 201);
    }


    public function firstOfRoom(Request $request)
    {
        $OwnerId = $request->owner_id;
        if (!$OwnerId) return Common::apiResponse(0, 'missing param', null, 422);
        $firstRoomOwner = $this->roomService->getFirstRoomOwner($OwnerId);
        return Common::apiResponse(1, '', ['user' => new UserResource($firstRoomOwner->sender), 'total' => (int)$firstRoomOwner->total], 200);
    }

    public function roomMode(Request $request)
    {
        $room = Room::query()->where('uid', $request->owner_id)->first();
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);
        if ($room->mood == 1) {
            $mode = 'party';
        } else if ($room->mood == 3) {
            $mode = 'cinema';
        } else {
            $mode = 'topCenter';
        }
        $ms   = [
            'messageContent' => [
                'message' => 'roomMode',
                'mode' => $mode
            ]
        ];
        $json = json_encode($ms);
        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
        return Common::apiResponse(1, 'done', null, 201);
    }

    public function changeMode(Request $request)
    {
        $currentMode = $request->mode;
        if ($currentMode == null || (!$request->owner_id && !$request->room_id)) return Common::apiResponse(0, 'missing param', null, 422);
        return $this->roomService->changeMode($request, $currentMode);
    }

    public function changeMicMode(Request $request)
    {
        $currentMode = $request->mode;
        if ($currentMode == null || !$request->owner_id) return Common::apiResponse(0, 'missing param', null, 422);
        return $this->roomService->changeModeMic($request, $currentMode);
    }


    /**
     * @return JsonResponse
     * @throws ValidationException
     */
    public function sendComment(CommentRequest $request): JsonResponse
    {
        try {
            (new RoomComments($this->repo))->sendComments($request->user(), $request->validated());
        } catch (NotInfCoins $e) {
            // The only business rejection (balance < special_bar_coin) keeps
            // its legacy 407 contract.
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        } catch (Throwable $e) {
            // Any other failure is a server bug, not "not enough coins" —
            // don't disguise it as a silent 407.
            Log::error('sendComment (yellow banner) failed', [
                'room_id' => $request->validated()['room_id'] ?? null,
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);
            return Common::apiResponse(false, 'Something went wrong', null, 500);
        }
        return Common::apiResponse(true, 'Yellow banner Done', null, 200);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|object|\Illuminate\Database\Eloquent\Builder $room
     * @param Request $request
     * @return false|string
     */
    public function changeBackground(Room $room, int $owner_id, string $image = ''): string|false
    {
        $data = [
            "messageContent" => [
                "message"       => "changeBackground",
                "imgbackground" => $image ?: "",
                "roomIntro"     => $room->room_intro ?: "",
                "roomImg"       => $room->room_cover ?: "",
                "room_type"     => @$room->myType->name ?: "",
                "room_name"     => @$room->room_name ?: ""
            ]
        ];
        $json = json_encode($data);
        //        Common::sendToStream('SendCustomCommand', $room->id, $owner_id, $json);
        return $json;
    }

    public function change_game_room(Request $request)
    {

        $currentGame = $request->game_id;
        if ($currentGame == null || !$request->owner_id) return Common::apiResponse(0, 'missing param', null, 422);
        $game = AllGame::find($currentGame);
        if (!$game) return Common::apiResponse(false, 'this game does not exists');

        $room = Room::query()->where('uid', $request->owner_id)->first();
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);
        $room->game_id =  $currentGame;
        $room->mode = 4;
        $room->save();

        $data = [
            "messageContent" => [
                "message"       => "changeRoomGame",
                "game_url"     => $game->mini_url ?: "",
            ]
        ];
        $json = json_encode($data);
        Common::sendToStream('SendCustomCommand', $room->id, $request->owner_id, $json);

        return Common::apiResponse(1, 'success', 200);
    }

    public function disable_writing(Request $request, $room_id)
    {
        $user_id = Auth::id();
        try {
            $room = $this->roomService->disableWriting($room_id);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
        $ms = [
            'messageContent' => [
                'message' => 'banRoomWriting',
                'userId' => $user_id,
                'writing_disabled' => $room->writing_disabled
            ]
        ];

        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, json_encode($ms));

        return Common::apiResponse(
            1,
            'writing permission is changed',
            [
                "room_id" => $room->id,
                "writing_disabled" => $room->writing_disabled
            ],
            200
        );
    }


    public function changeRoomImage(Request $request)
    {
        $ownerId = $request->owner_id;


        try {
            $room = $this->roomService->changeRoomImage($ownerId);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }

        $json = $this->changeBackground($room, $request->owner_id, PK_IMAGE);

        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);

        return Common::apiResponse(true, __('success process'));
    }

    public function adminOwner(Request $request)
    {
        $user = $request->user();
        if (!$request->type || !$request->room_id) return Common::apiResponse(0, 'missing param', null, 422);

        try {
            $check = $this->roomService->adminOwner($request, $user);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }


        return Common::apiResponse(true, __('success process'), $check, 200);
    }

    public function gameRoom()
    {
        request()->default_background = \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img;
        $game_id = request("game_id");
        $rooms = $this->roomService->getRoomsForGame($game_id);
        return Common::apiResponse(true, '', RoomResource::collection($rooms), 200);
    }

    public function userRooms()
    {
        $user = \Auth::user();
        $room = $this->roomService->userRooms($user->id);
        if ($room != null) {
            $room = new RoomResource($room);
        }
        return Common::apiResponse(true, '', $room, 200);
    }

    public function blackList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'        => 'required|integer|exists:rooms,id',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $userId = $request->user()->id;
            $room = Room::findOrFail($request->room_id);
            if (!\App\Helpers\RoomHelper::checkUserIsAdminOrOwner($room->room_admin ?? '', $room->uid)) {
                return Common::apiResponse(0, 'you do not have permission', null, 403);
            }
            // Authoritative, valid-only (active + non-expired) banned user ids. The old
            // explode(room_black) returned "userId#timestamp" tokens, so whereIn never
            // matched and expired/timed bans were shown as still active.
            $blacklistRepo = app(\App\Repositories\RoomBlacklistRepository::class);
            $ids = $blacklistRepo->getBlacklistedUserIds($room->id);
            $data = UserResource::collection(User::query()->whereIn('id', $ids)->get());
            return   Common::apiResponse(true, '', $data, 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function commentStatus($roomId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $result = $this->roomService->commentStatus($roomId, $request);

            $message = ($result == 1) ? 'comment_closed' : 'comment_opened';

            return Common::apiResponse(true, "messages.$message", [], 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }
    public function addBlock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'user_id' => 'required|integer|exists:users,id',
            'duration' => 'nullable|integer|min:60', // Duration in seconds, minimum 60 seconds
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $userId = $request->user()->id;
            $room = Room::with('roomVisitors')->findOrFail($request->room_id);
            $userToBlock = $request->user_id;
            $roomVisitors = $room->roomVisitors->pluck('user_id')->toArray();

            if (!in_array($userToBlock, $roomVisitors)) {
                return Common::apiResponse(0, 'This user is not in this room', null, 404);
            }

            // Owner or an admin with the ban_users power may ban; owner immune.
            if (!\App\Helpers\RoomHelper::can((int) $room->id, 'ban_users')) {
                return Common::apiResponse(0, 'You do not have permission to perform this action', null, 403);
            }
            if ($userToBlock == $room->uid) {
                return Common::apiResponse(0, 'cant block this user', null, 422);
            }

            $blacklistRepo = app(\App\Repositories\RoomBlacklistRepository::class);

            // Check if the user is already blacklisted
            if ($blacklistRepo->isBlacklisted($room->id, $userToBlock)) {
                return Common::apiResponse(0, 'This user is already in the blacklist', 400);
            }

            // Add the user to the blacklist (dual-write to both new table and legacy column)
            $duration = $request->duration; // null for permanent ban
            $reason = $request->reason;

            $blacklistRepo->addBan($room->id, $userToBlock, $userId, $duration, $reason);

            return Common::apiResponse(true, 'User added to the blacklist', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }
    public function removeBlock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $userId = $request->user()->id;
            $room = Room::findOrFail($request->room_id);

            // Owner or an admin with the ban_users power may unban.
            if (!\App\Helpers\RoomHelper::can((int) $room->id, 'ban_users')) {
                return Common::apiResponse(0, 'you do not have permission', null, 403);
            }

            $blacklistRepo = app(\App\Repositories\RoomBlacklistRepository::class);
            $userToRemove = $request->user_id;

            // Check if user is in blacklist
            if (!$blacklistRepo->isBlacklisted($room->id, $userToRemove)) {
                return Common::apiResponse(0, 'this user not in black list', 400);
            }

            // Remove from blacklist (dual-write to both new table and legacy column)
            $blacklistRepo->removeBan($room->id, $userToRemove);

            return Common::apiResponse(true, 'block removed', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }
    public function roomUserDetails($id)
    {
        try {
            $room = $this->roomServiceMain->roomDetails($id);
            return   Common::apiResponse(true, 'done', new RoomDetailsResource($room));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function roomGifts($id)
    {

        $perPage = request('per_page', 10);

        $result = Room::where('uid', $id)->first()?->gifts()->paginate($perPage);
        return Common::apiResponse(true, 'done', GiftRoomResource::collection($result));
    }


    public function check_room(Request $request)
    {

        $request['show'] = true;
        $id = $request->room_id;
        $room = $this->roomService->findRoom($id);
        if (!$room) {
            return Common::apiResponse(0, 'Room not found', null, 404);
        }

        $data = [
            'is_live' => $room->is_live ? true : false,
            'is_locked' => empty($room->room_pass) ? false : true,

        ];
        return Common::apiResponse(true, '', $data, 200);
    }


    private function updateMicrophone($room_uid, $user_id)
    {
        $user = User::query()->find($user_id);
        if (!$user) return;
        // Charisma is client-side seat state now; this only performs the mic-hand
        // bookkeeping (the prior ExtraDataInRoom cleanup is gone).
        Common::go_microphone_hand($room_uid, $user_id);
    }

    private function updateMicrophone2($room_uid, $user_id)
    {
        $user = User::query()->find($user_id);
        if (!$user) return;
        // Charisma is client-side seat state now; this only performs the mic-hand
        // bookkeeping (the prior ExtraDataInRoom cleanup is gone).
        Common::go_microphone_hand_2($room_uid, $user_id);
    }
}
