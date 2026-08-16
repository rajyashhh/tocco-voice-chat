<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Facades\UserHandling;
use App\Helpers\WebPHelper;
use App\Models\AllGame;
use App\Models\Room;
use App\Models\User;

use App\Helpers\Common;
use App\Models\LiveTime;
use Illuminate\Support\Facades\Auth;
use App\Models\EnteredRoom;
use App\Models\RoomCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditRoomRequest;
use App\Models\RequestBackgroundImage;

use App\Repositories\Room\RoomRepoInterface;
use App\Http\Resources\Api\V1\BoxUseResource;
use App\Http\Services\ProfileRelationsService;
use App\Http\Resources\Api\V1\MiniUserResource;
use App\Http\Resources\Api\V1\RoomUserResource;
use App\Http\Resources\Api\V1\EnterRoomCollection;
use App\Http\Services\EnterRoomService;
use App\Tik\Services\EnteranceRoomServices;
use Illuminate\Support\Facades\Validator;
use Modules\CP\Entities\CpRoomHistory;
use Illuminate\Support\Facades\Crypt;

class EnteranceController extends Controller
{

    protected $repo;
    protected $enteranceRoomService;

    public function __construct(RoomRepoInterface $repo, EnteranceRoomServices $enteranceRoomService)
    {
        $this->repo = $repo;
        $this->enteranceRoomService = $enteranceRoomService;
    }

    public function updateRoomCountFromStream(Request $request)
    {
        return $this->enteranceRoomService->updateRoomCountFromStream($request);
    }

    public function updateRoomCountFromStream2(Request $request)
    {
        return $this->enteranceRoomService->updateRoomCountFromStream2($request);
    }

    public function checkSignature($secert, $signature, $timestamp, $nonce)
    {
        $secert = 'a23b121a64ee9fab4567a2d75d00269d';
        $signature = "e95d06c85fa9c0d296e3d0077245f9dcf5de23eb";
        $timestamp = "1711027495";
        $nonce = "7348807134281767609";

        $tmpArr = array($secert, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function updateMicrophone($room_uid, $user_id)
    {
        $user = User::query()->find($user_id);
        if (!$user) return;
        // Charisma is client-side seat state now; this only performs the mic-hand
        // bookkeeping (the prior ExtraDataInRoom cleanup is gone).
        Common::go_microphone_hand($room_uid, $user_id);
    }

    protected function updateRoomVisitorsBasedOnEvent($event, $room, $userId)
    {
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);

        if ($event == 'room_login') {
            if (!$visitorRepo->isVisitor($room->id, $userId)) {
                $visitorRepo->addVisitor($room->id, $userId);
            }
        } elseif ($event == 'room_logout') {
            UserHandling::calcTime($userId);
            $this->updateMicrophone($room->uid, $userId);
            if ($visitorRepo->isVisitor($room->id, $userId)) {
                $visitorRepo->removeVisitor($room->id, $userId);
            }
        }

        // Return visitor IDs for backward compatibility
        return $visitorRepo->getVisitorIds($room->id)->toArray();
    }

    //    public function checkSignature($signature,$timestamp,$nonce)
    //    {
    
    //        $tempArr = [$secret, (string)$timestamp, $nonce];
    //        sort($tempArr, SORT_STRING);
    //
    //        $tmpStr = implode('', $tempArr);
    //        $calculatedSignature = sha1($tmpStr);
    //        return $signature == $calculatedSignature;
    //    }

    public function updateRoomCount(Request $request)
    {
        if (!$request->header('X-Pusher-Key') === env('PUSHER_APP_KEY')) {
            abort(403, 'Invalid Pusher webhook request');
        }
        $name = $request->events[0]['name'];
        $user_id = $request->events[0]['user_id'];

        if ($name !== 'member_added') {
            // RemoveUserFromRoomJob::dispatch( $user_id);
            $user = User::find($user_id);
            if ($user) {
                $room = Room::where('uid', $user->now_room_uid)->first();
                if ($room) {

                    $user->now_room_uid = 0;
                    $user->update();

                    $room->count_room_socket -= 1;
                    $room->update();
                }
            }
        }
        return response()->json(['status' => 'Webhook received']);
    }

    public function usersRoom(Request $request, ProfileRelationsService $profileRelationsService)
    {
        //        $user_id  = Auth::id();
        $usersIds = (array)$request->users_ids;
        $users = User::withoutAppends()->with([
            'packs' => function ($query) {
                return $query->whereIn('type', [5, 18]);
            },
            'profile',
            'UserVip',
            'dress2',
            'mangerType'
        ])
            ->whereIn('id', $usersIds)->get();

        [$senderLevels, $receivedImage] = $profileRelationsService->getLevelsSenderAndReceiver($users);
        RoomUserResource::initializeData($senderLevels, $receivedImage);
        $data = RoomUserResource::collection($users);
        return Common::apiResponse(1, '', $data);
    }

    public function usersRoomVisitor(Request $request, ProfileRelationsService $profileRelationsService)
    {
        //        $user_id  = Auth::id();
        $ownerId = @$request->owner_id;
        if (!$ownerId) return Common::apiResponse(false, __('room not found'));
        $room = Room::withoutAppends()->where('uid', $ownerId)->first();
        if (!$room) return Common::apiResponse(false, __('room not found'));

        // Use repository to get visitor IDs (avoid N+1 query issue)
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $usersIds = $visitorRepo->getVisitorIds($room->id)->toArray();

        $users = User::withoutAppends()->with([
            'packs' => function ($query) {
                return $query->whereIn('type', [5, 18]);
            },
            'profile',
            'UserVip',
            'dress2',
            'mangerType'
        ])
            ->whereIn('id', $usersIds)->get();

        [$senderLevels, $receivedImage] = $profileRelationsService->getLevelsSenderAndReceiver($users);
        RoomUserResource::initializeData($senderLevels, $receivedImage);
        $data = RoomUserResource::collection($users);
        return Common::apiResponse(1, '', $data);
    }


    /**
     * @throws \Exception
     */
    public function enter_room(Request $request, EnterRoomService $enterRoomServices): JsonResponse
    {
        $user     = $request->user();
        $roomId   = (int)$request->input('room_id');
        $roomPass = $request->input('room_pass');

        if (!$roomId) {
            return $this->errorResponse(__('Please provide a room id.'), 422);
        }

        $room = $this->findRoom($roomId);
        if (!$room) {
            return $this->errorResponse(__('Room not found.'), 404);
        }
        $room->load('microphones.user.profile');

        if ($banResponse = $enterRoomServices->checkRoomBan($user, $room)) {
            return $banResponse;
        }

        $type  =  $room->type;
        $audioRoom = Common::getSettingValue('audio_room') ?? 1;
        if ($type == 'audio'  && !$audioRoom) return Common::apiResponse(false, 'Audio rooms are disabled', null, 400);

        // A live broadcast is OVER the moment its host closes it — a stale
        // card/list entry must not let viewers in. The owner is exempt:
        // re-entering resumes the broadcast (handleOwnerLogic flips is_live
        // back on).
        if ($type !== 'audio' && $room->uid != $user->id && !$room->is_live) {
            return $this->errorResponse(__('This live stream has ended'), 423);
        }

        $this->setDefaultBackground();

        return $this->handleRoomType($type, $user, $request, $roomPass, $room);
    }


    private function errorResponse(string $message, int $code, array $extra = []): JsonResponse
    {
        return Common::apiResponse(0, $message, $extra ?: null, $code);
    }

    private function findRoom(int $roomId): ?Room
    {
        return Room::with([
            'roomLevel',
            'owner.profile',
            'owner.UserVip.OVip',
            'owner.specialId.ware',
            'owner.color_image',
            'owner.country',
            'taskStream',
            'taskStreamRoom',
            'roomCategory',
            'family',
            'backgroundImage',
            'background',
            'defaultBackground',
            'roomVisitors',
            'game',
            // Seats render in EnterRoomCollection::getMicrophones2(): it reads each
            // seat's user + profile.avatar. Without eager-loading these were lazy
            // per seat (1 + N + N queries on every room entry) — a tail-latency
            // source on this hot path. Eager-load eliminates the N+1.
            'microphones.user.profile',
        ])->find($roomId);
    }

    private function isRoomBanned(int $ownerId, string $roomType): bool
    {
        return Common::ifRoomHasband($ownerId, $roomType);
    }

    private function setDefaultBackground(): void
    {
        request()->default_background = \DB::table('backgrounds')
            ->where('enable', 1)
            ->orderBy('id')
            ->value('img');
    }

    private function handleRoomType(?string $type, $user, Request $request, $roomPass, Room $room): JsonResponse
    {
        return match ($type) {
            'audio' => $this->enteranceRoomService->enterRoom($user, $request, $roomPass, $room),
            'live'  => $this->enteranceRoomService->enterLiveRoom($user, $request, $roomPass, $room),
            default => $this->errorResponse(__('Invalid room type. Allowed types: audio, live.'), 422),
        };
    }

    private function updateRoom($user_id, $owner_id, Room &$room)
    {
        $this->updateRoomVisitors($user_id, $owner_id, $room);

        if ($room->charizma_status && ($room->charizma_timestamp + 86400) < now()->timestamp) {
            $room->charizma_timestamp = null;
            $room->charizma_status = false;
            // Charisma is client-side now: just cue clients to hide/zero the seat
            // charisma (no backend storage to clear).
            Common::sendToStream('SendCustomCommand', $room->id, 0, json_encode([
                'messageContent' => ['message' => 'closeCharisma'],
            ]));
        }

        $room->save();
    }

    /**
     * @deprecated This method is not used anymore. Visitor management moved to RoomVisitorRepository
     */
    private function updateRoomVisitor($user_id, $owner_id, Room $room)
    {
        if ($user_id == $owner_id) {
            $room->room_status = 1;
            //            $room->save();
        }

        // Use repository for visitor operations (includes dual-write to legacy column)
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        if (!$visitorRepo->isVisitor($room->id, $user_id)) {
            $visitorRepo->addVisitor($room->id, $user_id);
            $room->count_room_socket = $visitorRepo->getVisitorCount($room->id);
        }
        $room->save();
    }

    //exit the room
    public function quit_room(Request $request)
    {

        if (!$request->owner_id) Common::apiResponse(false, __('api_responses.missing_owner_id'), null, 422);
        $user_id = $request->user()->id;
        $room = Room::query()->where('uid', $request->owner_id)->first();
        if ($room) {
            $room->count_room_socket -= 1;
            if ($room->count_room_socket < 0) {
                $room->count_room_socket = 0;
            }
            $room->enableSaving = false;
            $room->save();
        }
        // Charisma is client-side seat state now: the leaving user's seat charisma
        // resets on the client — no backend reset/broadcast on room quit.
        $res = Common::quit_hand($request->owner_id, $user_id);
        $visitor_ids_list = explode(',', $res);
        $user = $request->user();
        $user->now_room_uid = 0;
        $user->save();
        $this->calcTime($user_id);

        $this->handleLeaveCp($user, $room);

        return Common::apiResponse(true, 'exited', ['visitor_ids_list' => $visitor_ids_list]);
    }

    public function handleLeaveCp($user, $room)
    {
        $userId = $user->id;
        $this->removeUserCpInRoom($userId);
        return $this->sendCpLovelyMessage($room, $user);
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

    public function sendCpLovelyMessage($room, $user)
    {
        $cpRoomHistories = CpRoomHistory::where("room_id", $room->id)->get(['index1', 'index2']);
        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        $json = $this->cpMapJson($indices);

        Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
    }

    public function removeUserCpInRoom(mixed $userId): void
    {
        CpRoomHistory::where("user_one_id", $userId)
            ->orWhere("user_two_id", $userId)->delete();
    }

    public function out_room(Request $request)
    {
        $uid = $request->owner_id ?: 0;
        $black_id = (int) ($request->user_id ?: 0);
        $duration = $request->minutes ?: 5;
        if (!$uid || !$black_id) return Common::apiResponse(0, 'invalid data', null, 422);
        if (!Common::can_kick($black_id)) return Common::apiResponse(0, 'cant kick this user', null, 403);

        $room = Room::where('uid', $uid)->first();
        if (!$room) return Common::apiResponse(0, 'room not found', null, 422);

        $room_id = $room->id;

        // Use new RoomBlacklistRepository with dual-write
        $blacklistRepo = app(\App\Repositories\RoomBlacklistRepository::class);
        $durationSeconds = $duration * 60; // Convert minutes to seconds

        // Check if already banned and update, or add new ban
        if ($blacklistRepo->isBlacklisted($room_id, $black_id)) {
            // Remove old ban
            $blacklistRepo->removeBan($room_id, $black_id);
        }

        // Add new ban with updated duration
        $result = $blacklistRepo->addBan(
            $room_id,
            $black_id,
            Auth::id(), // banned_by
            $durationSeconds,
            'Kicked out for ' . $duration . ' minutes'
        );

        if ($result) {
            //exit the room
            Common::quit_hand($uid, $black_id);
            $user = User::find($black_id);
            if ($user) {
                $user->now_room_uid = 0;
                $user->save();
            }
            $mc = [
                'messageContent' => [
                    'message' => 'kickout',
                    'duration' => $duration
                ]
            ];
            $json = json_encode($mc);
            $b = User::find($black_id);
            $n = 'nan';
            if ($b) {
                $n = $b->name ?: 'nan';
            }
            Common::sendToStream_4('SendCustomCommand', $room_id, $uid, $black_id, $json);
            $this->calcTime($black_id);
            Common::sendToStream_2('SendBroadcastMessage', $room_id, $uid, 'room', " تم طرد $n");
            return Common::apiResponse(1, 'success');
        } else {
            return Common::apiResponse(0, 'fail', null, 400);
        }
    }

    private function enterTheRoomCreateOrUpdate($user_id, $owner_id, $room_id)
    {
        EnteredRoom::query()->updateOrCreate(
            [
                'uid' => $user_id,
                'ruid' => $owner_id,
                'rid' => $room_id
            ],
            [
                'entered_at' => now()
            ]
        );
    }

    public function calcTime($uid)
    {
        $timer = LiveTime::query()->where('uid', $uid)->where('end_time', null)->first();
        if ($timer) {
            $hours = round((time() - $timer->start_time) / (60 * 60), 2);
            $timer->end_time = time();
            $timer->hours = $hours;
            $d = LiveTime::query()->where('uid', $uid)->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])->where('days', '>=', 1)->exists();
            if (!$d) {
                if ($hours >= 1) {
                    $timer->days = 1;
                }
            }

            $timer->save();
        }
    }


    public function update(EditRoomRequest $request, $id)
    {
        try {
            $user = $request->user();
            $type = $request->get('type', 'audio');

            $room = $this->repo->findByType($id, $type);
            if (!$room) {
                return Common::apiResponse(false, 'Room not found', null, 404);
            }
            if ($user->id != $room->uid) return Common::apiResponse(0, __('you don not have permission'), null, 404);

            if ($request->room_name) {
                $room->room_name = $request->room_name;
            }

            if ($request->hasFile('room_cover')) {

                //  $room->room_cover = Common::upload('rooms', $request->file('room_cover'));

                $validation = Common::validateMedia($request->file('room_cover'), 'room');
                if (!$validation['valid']) {
                    return Common::apiResponse(false, $validation['error'], null, 404);
                }

                $room->room_cover = Common::uploadOptimized(
                    'rooms',
                    $request->file('room_cover'),
                    'room',
                    Room::class,
                    $room->id,
                    'room_cover'
                );
            }

            if ($request->free_mic) {
                $room->free_mic = $request->free_mic;
            }

            if ($request->room_intro) {
                $room->room_intro = $request->room_intro;
            }

            if ($type) {
                $room->type = $type;
                if ($request->type == 'live') {
                    $room->is_live = true;
                } else {
                    $room->is_live = false;
                }
            }

            if ($request->room_pass) {
                $room->room_pass = $request->room_pass;
            }

            if (!is_null($request->mode)) {
                $this->changeMode($request, $request->mode, $room);
            }

            if ($request->room_type) {
                if (!RoomCategory::query()->where('id', $request->room_type)->where('enable', 1)->exists()) return Common::apiResponse(0, 'type not found', null, 404);
                $room->room_type = $request->room_type;
            }

            if ($request->room_class) {
                if (!RoomCategory::query()->where('id', $request->room_class)->where('enable', 1)->exists()) return Common::apiResponse(0, 'class not found', null, 404);
                $room->room_type = $request->room_type;
            }
            $background_me = '';

            if ($request->room_background) {
                /*if (!Background::query ()->where ('id',$request->room_background)->where ('enable',1)->exists ()){
                    return Common::apiResponse (0,'background not found',null,404);
                }*/
                if ($request->change == 'app') {
                    $room->room_background = $request->room_background;

                    RequestBackgroundImage::query()->where('owner_room_id', $room->uid)->where('status', 1)->update(['status' => 3]);
                }
                if ($request->change == 'me') {

                    RequestBackgroundImage::query()->where('owner_room_id', $room->uid)->where('id', '!=', $request->room_background)->where('status', 1)->update(['status' => 3]);
                    $background_update = RequestBackgroundImage::where('id', $request->room_background)->first();
                    $background_update->status = 1;
                    $background_update->save();
                    $background_me = $background_update->img;
                    $room->room_background = null;
                }
            }
            //    $this->repo->save ($room);

            $room->save();
            $room = Room::with(['myType', 'backgroundImage', 'background'])->find($room->id);

            $request['owner_id'] = $room->uid;
            $is_locked = false;
            if ($room->room_pass != null) {
                $is_locked = true;
            }
            $data = [
                "messageContent" => [
                    "message" => "changeBackground",
                    "imgbackground" => $background_me ? @$background_me : $room->final_room_image,
                    "roomIntro" => $room->room_intro ?: "",
                    "roomImg" => $room->room_cover ?: "",
                    "room_type" => @$room->myType->name ?: "",
                    "room_name" => @$room->room_name ?: "",
                    "is_locked" => @$is_locked ?: false,
                    "type_room" => @$room->type_room ?: "",
                    "is_live" => @$room->is_live ?: false,
                ]
            ];
            $json = json_encode($data);
            Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
            $request->is_update = true;
            // return 0;
            $room_info = (new EnterRoomCollection($room, $request->user()->id));
            $room_info = $room_info->toArray($request);

            return Common::apiResponse(true, '', $room_info);
        } catch (\Exception $exception) {
            return Common::apiResponse(false, 'failed', $exception->getMessage(), 400);
        }
    }

    public function changeMode($request, $currentMode, Room $room)
    {
        $lastMode = $room->mode;

        $room->mode = $currentMode;
        $jsons = [];
        $map = [];
        if ($currentMode == '1') {
            $mode = 'party';
        } elseif ($currentMode == '2') {
            $mode = 'seats12';
        } elseif ($currentMode == '5') {
            $mode = 'cinema';
        } elseif ($currentMode == '4') {
            $mode = 'game';
            if (!$request->game_id) return Common::apiResponse(0, 'please send game_id', null, 404);
            $game = AllGame::find($request->game_id);
            if (!$game) return Common::apiResponse(false, 'this game does not exists');

            $room->game_id = $request->game_id;
            $map['game_url'] = $game->mini_url;
        } else {
            $mode = 'topCenter';
        }
        $ms = [
            'messageContent' => array_merge($map, ['message' => 'roomMode', 'mode' => $mode])
        ];
        $json = json_encode($ms);
        $jsons[] = $json;
        if ($lastMode == '3' && $currentMode != '3') {
            $jsons[] = $this->changeBackground($room, $room->uid, $this->getRoomBackground($room));
        }
        Common::sendToStream3('SendCustomCommand', $room->id, $request->user()->id, $jsons);
    }

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
        return $json;
    }

    public function getRoomBackground(?Room $room)
    {
        if ($room == null) return '';
        return $room->final_room_image;
    }

    public function updateRoomVisitors($user_id, $owner_id, Room &$room): void
    {
        //        $room->count_room_socket += 1;
        //        $visitors                = explode(',', $room->room_visitor);
        //        if ($visitors[0] == '') $visitors = [];
        //        if (!in_array($user_id, $visitors)) {
        //            $visitors[]         = $user_id;
        //            $visitors           = array_unique($visitors);
        //            $visitors           = trim(implode(",", $visitors), ",");
        //            $room->room_visitor = $visitors;
        //        }

    }

    public function invite_user(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'owner_id' => 'required|exists:rooms,uid',
        ]);

        if ($validator->fails()) {
            $errors = implode(',', $validator->errors()->all());
            return Common::apiResponse(0, $errors);
        }
        $user = $request->user();
        try {
            $send = $this->enteranceRoomService->makeRequestInviteRoom($user, $request);
            return $send;
        } catch (\Exception $th) {
            // \Log::error('Error inviting to room: ' . $th->getMessage());
            return Common::apiResponse(0,     $th->getMessage(), [], 500);
        }
    }
}
