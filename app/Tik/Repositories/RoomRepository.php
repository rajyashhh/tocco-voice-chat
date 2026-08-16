<?php

namespace App\Tik\Repositories;

use Auth;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\Room;
use App\Models\User;
use App\Models\EnteredRoom;
use App\Models\RoomPrivateMessages;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\Api\V1\NowRoomResource;
use phpDocumentor\Reflection\PseudoTypes\True_;
use App\Http\Resources\Api\V1\NowRoomUserResource;

/** @property Room $model*/
class RoomRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Room);
    }

    public function findRoomUser($userId, $withoutAppends = true)
    {
        $model = $this->model;
        if ($withoutAppends) $model = $model->withoutAppends();
        return $model->where('uid', $userId)->with(['owner', 'roomCategory', 'family'])->first();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function findRoomTypeUser($userId, $type = 'audio', $withoutAppends = true)
    {
        $model = $this->model;
        if ($withoutAppends) $model = $model->withoutAppends();
        return $model->where('type', $type)->where('uid', $userId)->with(['owner', 'roomCategory', 'family'])->first();
    }
    public function findAudioRoomUser($userId, $withoutAppends = true)
    {
        $model = $this->model;
        if ($withoutAppends) $model = $model->withoutAppends();
        return $model->where('type', 'audio')->where('uid', $userId)->with(['owner', 'roomCategory', 'family'])->first();
    }

    public function findRoomId($id, $withoutAppends = true)
    {
        $query = $this->model;

        if ($withoutAppends) {
            $query = $query->withoutAppends();
        }
        $query = $query->select(['id', 'uid', 'room_admin']);
        return $query->where('id', $id)->first();
    }

    public function findRoomUserByType($userId, $type, $withoutAppends = true)
    {
        $model = $this->model;
        if ($withoutAppends) {
            $model = $model->withoutAppends();
        }

        switch ($type) {
            case 'audio':
                return $model->where('uid', $userId)
                    ->where('type', 'audio')
                    ->with(['owner', 'roomCategory', 'family'])
                    ->first();

            case 'live':
                return $model->where('uid', $userId)
                    ->where('type', 'live')
                    ->with(['owner', 'roomCategory', 'family'])
                    ->first();

            default:
                return null;
        }
    }


    public function findRoomAdmins($userId, $withoutAppends = true)
    {
        $query = $this->model;

        if ($withoutAppends) {
            $query = $query->withoutAppends();
        }
        $query = $query->select(['id', 'uid', 'room_admin']);
        //        $query = $query->with(['family:id,user_id,name,image']);
        return $query->where('uid', $userId)->first();
    }


    public function findRoomUserEnable($userId)
    {
        return $this->model->where('uid', $userId)->with(['owner', 'roomCategory', 'family'])->where('room_status', 1)->first();
    }

    public function findRoomUserEnableAudio($userId)
    {
        return $this->model->where('uid', $userId)->with(['owner', 'roomCategory', 'family'])->where('room_status', 1)->where('type', 'audio')->first();
    }

    public function findUserRoom($ownerId, $selectRow = "*")
    {
        return $this->model->withoutAppends()->where(['uid' => $ownerId])->selectRaw($selectRow)->first();
    }

    public function findTypeUserRoom($ownerId, $type = 'audio', $selectRow = "*")
    {
        return $this->model->withoutAppends()
            ->with('owner:id,uuid')
            ->where(['uid' => $ownerId])
            ->where('type', $type)
            ->selectRaw($selectRow)->first();
    }

    public function findUserRoomById($ownerId, $selectRow = "*")
    {
        return $this->model
            ->withoutAppends()
            ->with('owner:id,uuid')
            ->where(['id' => $ownerId])
            ->selectRaw($selectRow)
            ->first();
    }
    public function updateRoom($room)
    {
        $room->update();
    }

    public function createPrivetMessage($fromUserId, $toUserId, $message, $price)
    {
        return  RoomPrivateMessages::query()->create([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'message' => $message,
            'price' => $price
        ]);
    }


    public function findRoom($roomId)
    {
        return $this->model->find($roomId);
    }

    public function findRoomForShow($roomId)
    {
        // Eager-load ONLY relations the RoomResource reads unconditionally so the
        // room-show JSON stays byte-identical to HEAD while killing the owner-block N+1.
        // Relations the resource emits via whenLoaded/relationLoaded (game, taskStream,
        // taskStreamRoom, roomVisitorUsers) are intentionally NOT loaded; microphones is
        // re-queried by the resource via the builder, and roomVisitors count is unused.
        return $this->model
            ->with([
                'lastPk',
                'roomCategory',
                'boxUse',
                'roomLevel',
                'owner.profile',
                'owner.country',
                'owner.agency',
                'owner.color_image',
                'owner.specialId.ware',
                'owner.eligiblePacks.ware',
                'owner.enabledMedals',
                'owner.medals.achievementLevel.achievement',
            ])
            ->find($roomId);
    }

    public function getRooms($ids)
    {
        return $this->model->whereIn('uid', $ids)->where(function ($q) {
            $q->where('count_room_socket', '!=', 0);
        })->orderBy('hot', 'desc')->get();
    }

    public function updateMicRoom($room, $mic)
    {
        $room->microphone = $mic;
        return $room->save();
    }

    public function updateRoomStatus($userId, $isAvailable)
    {
        return $this->model->query()->where('uid', $userId)->update(['room_status' => $isAvailable ? 2 : 1]);
    }

    public function updateRoomUser($room)
    {
        $room->save();
        return true;
    }

    public function all($req, $ids = [], bool $shared = false)
    {
        $roomType = $req->room_type ?? 'audio';
        $user = $req?->user();
        $topRooms = (settings()->get('make_rooms_top') == 1) ?? false;

        // Hidden-room owners (type-16 packs) are the same for every caller and
        // this pluck is unbounded; cache it briefly instead of running it on
        // every request to the hottest endpoint.
        $blockedUserIds = \Illuminate\Support\Facades\Cache::remember(
            'rooms:blocked_user_ids',
            45,
            fn() => Pack::query()
                ->select('user_id')
                ->where('type', 16)
                ->where('is_used', 1)
                ->where(function ($q) {
                    $q->where('expire', 0)
                        ->orWhere('expire', '>=', now()->timestamp);
                })
                ->pluck('user_id')
                ->all()
        );

        // Shared build path (no authenticated viewer in the key): is_lucky_box
        // must NOT depend on the viewer's own picks here, otherwise the cached
        // payload would leak one viewer's pick-state to everyone. We stamp a
        // viewer-independent "room HAS an active, unexhausted box" existence flag
        // under the SAME column name; the real per-viewer truth is overlaid by
        // the controller on a deep clone of the cached array per request.
        $luckyBoxModel = $shared
            ? $this->model->withExists([
                'boxUse as is_lucky_box' => function ($q) {
                    $q->select('id')
                        ->where('not_used_num', '>', 0)
                        ->where('end_at', '>=', now()->timestamp)
                        ->limit(1);
                }
            ])
            : $this->model->withLuckyBoxFlag($user->id);

        $result = $luckyBoxModel
            ->select(['id', 'uid', 'room_name', 'room_background', 'room_cover', 'room_intro', 'level_id', 'room_status', 'room_pass', 'room_admin', 'room_black', 'room_speak', 'room_sound', 'microphone', 'free_mic', 'max_admin', 'is_recommended', 'is_popular', 'is_live', 'hot', 'pin', 'top_room', 'hour_hot', 'type', 'mode', 'created_at'])
            ->with([
                // Only the relations the list-path RoomResource actually reads.
                // Column selects added where the resource touches a single field,
                // so we stop hydrating full rows for the hottest endpoint.
                // Verified field-by-field against RoomResource::toArray (list path).
                'roomLevel',                                                      // room_level_image (audio)
                'backgroundImage:request_background_images.id,owner_room_id,img', // final_room_image
                'defaultBackground:id,img',                                       // final_room_image fallback
                'lastPk:id,room_id',                                              // is_pk
                'background:id,img',                                              // final_room_image fallback
                'roomVisitorUsers' => fn($q) => $q->with('profile:id,user_id,avatar')->limit(5), // visitors_images
                'roomCategory:id,type',                                           // is_party
                'roomVisitors',                                                   // count_room_socket_v2 (visitors_count) without per-row count query
                'boxUse:id,room_id,is_closed',                                    // have_luck_box
                'owner' => [
                    'enabledMedals',                       // medals
                    'agency:id,name',                      // agency
                    'country',                             // country (relation self-selects its columns)
                    'color_image',                         // owner_image_color
                    'specialId.ware',                      // owner_special_id
                    'eligiblePacks.ware',                  // country_hidden (getPackWithTypeV2) + owner_uuid (uuid_v2)
                    'profile:id,user_id,avatar',           // owner_image
                    'medals.achievementLevel.achievement', // achievement_images
                ],
            ])
            ->withCount('roomVisitors')
            ->whereHas('owner')
            ->whereNotIn('uid', $blockedUserIds)
            //        ->whereDoesntHave('owner.packs', function ($q) {
            //            $q->where('type', 16)
            //                ->where('is_used', 1)
            //                ->where(function ($q) {
            //                    $q->where('expire', 0)
            //                        ->orWhere('expire', '>=', now()->timestamp);
            //                });
            //        })
            ->where('room_status', 1);

        $result->orderByDesc('pin');

        if ($topRooms && $roomType != 'live') {
            $result->orderByRaw('is_top = 1 DESC');
        } else {
            $result->where(function ($query) {
                $query->whereHas('roomVisitors')->orWhere('pin', 1);
            });
        }
        $result->orderByDesc('room_visitors_count');

        $result->orderByDesc('hour_hot');

        if (!is_null($req->country_id)) {
            $result->whereHas('owner', function ($q) use ($req) {
                $q->where('country_id', $req->country_id);
            });
        }

        switch ($req->filter) {
            case 'boss':
                $roomIds = EnteredRoom::query()
                    ->where('uid', $user->id)
                    ->orderByDesc('entered_at')
                    ->pluck('rid')
                    ->toArray();
                $result->whereIn('id', $roomIds);
                break;

            case 'trend':
                $result->orderBy('top_room', 'DESC')
                    ->orderByDesc('session');
                break;

            case 'popular':
                $result->orderByDesc('top_room');
                break;

            case 'last_create':
                $result->whereDate('created_at', '>=', Carbon::now()->subDays(3))
                    ->orderByDesc('id');
                break;

            case 'pk':
                $result->has('lastPk');
                break;

            case 'party':
                $result->whereHas('roomCategory', function ($query) {
                    $query->where('type', 'party');
                });
                break;

            case 'recently':
            case 'festival':
                $result->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'interested':
                $roomTypes = EnteredRoom::query()
                    ->where('uid', $user->id)
                    ->where('entered_at', '>=', Carbon::now()->subDay())
                    ->with('room')
                    ->get()
                    ->pluck('room.room_type')
                    ->unique();

                $result->whereIn("room_type", $roomTypes)
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'following':
                $result->whereIn('uid', $user->followeds_ids())
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'friends':
                $result->whereIn('uid', $user->friends_ids())
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'nearby':
                $userLat = $user->lat;
                $userLong = $user->long;

                $result->selectRaw(
                    'rooms.*,
                    ( 6371 * acos( cos( radians(?) ) * cos( radians( owner.lat ) ) * cos( radians( owner.long ) - radians(?) ) + sin( radians(?) ) * sin( radians( owner.lat ) ) ) ) AS distance',
                    [$userLat, $userLong, $userLat]
                )
                    ->join('users as owner', 'rooms.uid', '=', 'owner.id')
                    ->orderBy('distance');
                break;
        }

        if (count($ids) > 0) {
            $result = $result->whereIn('uid', $ids);
        }

        return $result->when($roomType != 'live', function ($q) use ($roomType) {
            $q->where('type', $roomType);
        })->when($roomType == 'live', function ($q) {
            $q->whereIn('type', ['single_live', 'multi_live']);
        })->paginate(10);
    }



    public function getRoomsByGameId($gameId = null, array $with = [])
    {
        return $this->model->with($with)
            ->where('game_id', '!=', null)
            ->where('mode', 4)
            ->when(isset($gameId) && $gameId != 'null', function ($query) use ($gameId) {
                $query->where('game_id', $gameId);
            })->get();
    }

    public function randomOwner()
    {
        return $this->model->query()
            ->where('room_status', 1)
            ->where('uid', '!=', null)
            ->where(function ($q) {
                $q->where('count_room_socket', '!=', 0)->orWhere('is_afk', 1);
            })->pluck('uid')->random();
    }

    public function updateRoomBlack($room, $roomBlack)
    {
        $room->room_black = trim($roomBlack, ',');
        $this->updateRoomUser($room);
    }

    public function roomUsers($userId)
    {
        return $this->model->withoutAppends()->withCount('roomVisitors')->where('uid', $userId)->first();
    }

    public function mine($req, $id)
    {
        $user     = User::find($id);
        $query = $this->baseRoomQueryMyMine($user);


        $audio = (clone $query)->where('type', 'audio')->first();
        $live  = (clone $query)->where('type', 'live')->first();

        return [
            'audio' => $audio
                ? new RoomResource($audio)
                : (object)[],

            'live'  => $live
                ? new RoomResource($live)
                : (object)[],
        ];
    }

    public function getUserRooms($req, $id)
    {
        $user     = User::find($id);
        
        // Handle case where user is not found
        if (!$user) {
            return [
                'audio'     => null,
                'live'      => null,
                'nowRooms'  => [],
            ];
        }
        
        $query = $this->baseRoomQueryMine($user);


        $audio = (clone $query)->where('type', 'audio')->first();
        $live  = (clone $query)->where('type', 'live')->where('is_live', true)->first();
        $nowRooms  = $this->getNowRooms($user);
          //          'now_room'             => $this->formatNowRoom(),


        return [
            'audio' => $audio
                ? new RoomResource($audio)
                : (object)[],

            'live'  => $live
                ? new RoomResource($live)
                : (object)[],
            'now_room'  => $nowRooms
                ? $nowRooms
                : (object)[],
        ];
    }
    private function getNowRooms($user)
    {

        if (!$user->now_room_uid) return (object)[];

        $nowRoomOwner = $user->nowRoomOwner;
      //  dd($nowRoomOwner);

        if (!$nowRoomOwner) return (object)[];

        if ($nowRoomOwner->getPackWithTypeV3(16)) return (object)[];

        $resource = (new NowRoomUserResource($user->room))->toArray(request());

        return empty($resource) ? (object)[] : $resource;
    }

    private function getBlockedUserIds()
    {
        return Pack::query()
            ->select('user_id')
            ->where('type', 16)
            ->where('is_used', 1)
            ->where(function ($q) {
                $q->where('expire', 0)
                    ->orWhere('expire', '>=', now()->timestamp);
            })
            ->pluck('user_id');
    }

    private function baseRoomQuery($user, $blockedUserIds)
    {
        return $this->model
            ->where('type', 'live')
            ->where('is_live', true)
            // A room is listed only once the host has ACTUALLY started
            // broadcasting (first VIDEO track_published on the UTD-Stream
            // engine, set by RoomOccupancyReconciler::setBroadcasting). This is
            // the fix for the "early broadcast" bug: is_afk / is_live flip the
            // instant the host OPENS the room over HTTP (room preparation), so
            // gating on them listed still-preparing, empty rooms.
            ->where('is_broadcasting', true)
            ->select([
                'id',
                'uid',
                'room_name',
                'room_cover',
                'room_intro',
                'room_status',
                'room_pass',
                'room_admin',
                'room_black',
                'room_speak',
                'room_sound',
                'microphone',
                'free_mic',
                'max_admin',
                'is_recommended',
                'is_popular',
                'is_live',
                'hot',
                'pin',
                'top_room',
                'hour_hot',
                'type',
                'mode',
                'created_at'
            ])
            ->with([
                'backgroundImage:request_background_images.id,owner_room_id,img',
                'lastPk:id,room_id',
                'background:id',
                'roomVisitorUsers' => fn($q) => $q->limit(5),
                'myClass',
                'roomCategory:id,type',
                'myType',
                'roomVisitors.user.packs',
                'owner.enabledMedals',
                'owner.country',
                'owner.eligiblePacks.ware',
                'owner.profile',
                'owner.medals.achievementLevel.achievement',
                'boxUse',
            ])
            ->withCount('roomVisitors')
            ->whereHas('owner')
            ->whereNotIn('uid', $blockedUserIds)
            // NO whereHas(roomVisitors) gate: room_visitors rows are ephemeral
            // presence rows (swept on logout/cleanup), so requiring the owner's
            // own row hid live broadcasts — same class of bug as the
            // applyTopRoomsOrder gate already removed from liveRooms().
            // Verified in prod: room 10119 with is_live=1/is_afk=1 and 0
            // visitor rows was unlisted. Liveness = is_live + is_afk only.
            ->where('room_status', 1)
            ->orderByDesc('pin')
            ->orderByDesc('room_visitors_count')
            ->orderByDesc('hour_hot');
    }

    private function baseRoomQueryMyMine($user)
    {
        return $this->model
            ->where('uid', $user->id)
            ->select([
                'id',
                'uid',
                'room_name',
                'room_cover',
                'room_intro',
                'room_status',
                'room_pass',
                'room_admin',
                'room_black',
                'room_speak',
                'room_sound',
                'microphone',
                'free_mic',
                'max_admin',
                'is_recommended',
                'is_popular',
                'is_live',
                'hot',
                'pin',
                'top_room',
                'hour_hot',
                'room_background',
                'type',
                'mode',
                'level_id',
                'created_at'
            ])
            ->with([
                'roomLevel',
                'backgroundImage:request_background_images.id,owner_room_id,img',
                'lastPk:id,room_id',
                'background:id,img',
                'roomVisitorUsers' => fn($q) => $q->limit(5),
                'myClass',
                'roomCategory:id,type',
                'myType',
                'roomVisitors.user.packs',
                'owner.enabledMedals',
                'owner.country',
                'owner.eligiblePacks.ware',
                'owner.profile',
                'owner.medals.achievementLevel.achievement',
                'boxUse',
                'taskStream',
                'taskStreamRoom'
            ])
            ->withCount('roomVisitors')
            ->orderByDesc('pin')
            ->orderByDesc('room_visitors_count')
            ->orderByDesc('hour_hot');
    }

    private function baseRoomQueryMine($user)
    {
        $authUserId = request()->user()->id ?? $user->id;

        return $this->model
            ->where('uid', $user->id)
            ->select([
                'id',
                'uid',
                'room_name',
                'room_cover',
                'room_intro',
                'room_status',
                'room_pass',
                'room_admin',
                'room_black',
                'room_speak',
                'room_sound',
                'microphone',
                'free_mic',
                'max_admin',
                'is_recommended',
                'is_popular',
                'is_live',
                'hot',
                'pin',
                'top_room',
                'hour_hot',
                'room_background',
                'type',
                'mode',
                'created_at'
            ])
            ->with([
                'backgroundImage:request_background_images.id,owner_room_id,img',
                'lastPk:id,room_id',
                'background:id,img',
                'roomVisitorUsers' => fn($q) => $q->limit(5),
                'myClass',
                'roomCategory:id,type',
                'myType',
                'roomVisitors.user.packs',
                'owner.enabledMedals',
                'owner.country',
                'owner.eligiblePacks.ware',
                'owner.profile',
                'owner.medals.achievementLevel.achievement',
                'boxUse',
                'owner.chatRoomsAsUser' => function ($q) use ($authUserId) {
                    $q->where('user_id2', $authUserId)
                        ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                            $query->where('user_id', '<>', $authUserId)
                                ->where('status', '<>', 'seen');
                        }]);
                },
                'owner.chatRoomsAsUser2' => function ($q) use ($authUserId) {
                    $q->where('user_id', $authUserId)
                        ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                            $query->where('user_id', '<>', $authUserId)
                                ->where('status', '<>', 'seen');
                        }]);
                },
            ])
            ->withCount('roomVisitors')
            ->where('room_status', 1)
            ->orderByDesc('pin')
            ->orderByDesc('room_visitors_count')
            ->orderByDesc('hour_hot');
    }

    private function applyTopRoomsOrder($query, $topRooms)
    {
        if ($topRooms) {
            $query->orderByRaw('is_top = 1 DESC');
        } else {
            $query->where(function ($q) {
                $q->whereHas('roomVisitors')->orWhere('pin', 1);
            });
        }
    }

    private function applyCountryFilter($query, $countryId)
    {
        $query->whereHas('owner', fn($q) => $q->where('country_id', $countryId));
    }

    private function applyFilter($query, $filter, $user)
    {
        switch ($filter) {
            case 'boss':
                $roomIds = EnteredRoom::query()
                    ->where('uid', $user->id)
                    ->orderByDesc('entered_at')
                    ->pluck('rid')
                    ->toArray();
                $query->whereIn('id', $roomIds);
                break;

            case 'trend':
                $query->orderBy('top_room', 'DESC')->orderByDesc('session');
                break;

            case 'popular':
                $query->orderByDesc('top_room');
                break;

            case 'last_create':
                $query->whereDate('created_at', '>=', Carbon::now()->subDays(3))
                    ->orderByDesc('id');
                break;

            case 'pk':
                $query->has('lastPk');
                break;

            case 'party':
                $query->whereHas('roomCategory', fn($q) => $q->where('type', 'party'));
                break;

            case 'recently':
            case 'festival':
                $query->orderByDesc('top_room')->orderByDesc('session');
                break;

            case 'interested':
                $roomTypes = EnteredRoom::query()
                    ->where('uid', $user->id)
                    ->where('entered_at', '>=', Carbon::now()->subDay())
                    ->with('room')
                    ->get()
                    ->pluck('room.room_type')
                    ->unique();

                $query->whereIn("room_type", $roomTypes)
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'following':
                $query->whereIn('uid', $user->followeds_ids())
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'friends':
                $query->whereIn('uid', $user->friends_ids())
                    ->orderByDesc('top_room')
                    ->orderByDesc('session');
                break;

            case 'nearby':
                $this->applyNearbyFilter($query, $user);
                break;
        }
    }

    private function applyNearbyFilter($query, $user)
    {
        $query->selectRaw(
            'rooms.*,
            ( 6371 * acos( cos( radians(?) ) * cos( radians( owner.lat ) ) * cos( radians( owner.long ) - radians(?) ) + sin( radians(?) ) * sin( radians( owner.lat ) ) ) ) AS distance',
            [$user->lat, $user->long, $user->lat]
        )
            ->join('users as owner', 'rooms.uid', '=', 'owner.id')
            ->orderBy('distance');
    }



    public function liveRooms($req, $ids = [])
    {
        $user     = $req?->user();

        $blockedUserIds = $this->getBlockedUserIds();

        $query = $this->baseRoomQuery($user, $blockedUserIds);

        // Ordering only. The visibility gate (is_live + is_broadcasting) lives
        // in baseRoomQuery(); a fresh live with zero room_visitors rows is still
        // listed the moment the host actually publishes video, but a room that
        // is merely open/preparing (is_broadcasting = 0) is NOT.
        $query->orderByRaw('is_top = 1 DESC');

        if (!is_null($req->country_id)) {
            $this->applyCountryFilter($query, $req->country_id);
        }

        //        $this->applyFilter($query, $req->filter, $user);

        if (!empty($ids)) {
            $query->whereIn('uid', $ids);
        }
        return RoomResource::collection(
            $query->where('type', 'live')->paginate()
        );
    }
}
