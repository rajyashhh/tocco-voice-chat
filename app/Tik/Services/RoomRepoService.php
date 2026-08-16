<?php

namespace App\Tik\Services;


use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\WebPHelper;
use App\Models\Config;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use App\Models\Room;
use App\Models\User;
use App\Helpers\Common;
use App\Models\AllGame;
use App\Models\RoomCategory;
use App\Facades\UserHandling;
use GuzzleHttp\Promise\Utils;
use App\Classes\Room\RoomComments;
use App\Http\Services\RoomService;
use App\Traits\MultiQueryPagination;
use App\Models\RequestBackgroundImage;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\CountryRepository;
use App\Tik\Repositories\GiftLogRepository;
use App\Repositories\Room\RoomRepoInterface;
use App\Tik\Repositories\RequestBackgroundImageRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Modules\TaskStream\Services\TaskStreamService;
use App\Models\RoomMicrophone;

class RoomRepoService
{
    use MultiQueryPagination;
    protected $repo;
    /**
     * @param Model $model
     */
    public function __construct(
        private readonly RoomRepository $repository,
        private readonly UserRepository $userRepository,
        private readonly GiftLogRepository $giftLogRepository,
        private readonly RequestBackgroundImageRepository $requestBackgroundImageRepository,
        private readonly CountryRepository $countryRepository,
        RoomRepoInterface $repo,
    ) {}

    public function getAllRooms($request, bool $shared = false)
    {
        //        request()->default_background = \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img;
        return $this->repository->all($request, [], $shared);
    }

    public function getAllMine($request, $user_id)
    {
        return $this->repository->mine($request, $user_id);
    }

    public function getUserRooms($request, $user_id)
    {
        return $this->repository->getUserRooms($request, $user_id);
    }



    public function getAllLiveRooms($request)
    {
        return $this->repository->liveRooms($request);
    }


    /**
     * @throws \Exception
     */
    public function create($request, $user)
    {
        $userId = $user->id;
        $data = array_merge($request->all(), ['uid' => $userId]);
        unset($data['show']);
        $paidRoom = Config::where('name', 'paid_room')->first();

        if ($paidRoom && $paidRoom->value && $request->type === 'audio') {
            $paidRoomAmount = Config::where('name', 'paid_room_amount')->first();
            if ($user->di < $paidRoomAmount->value) {
                throw new \Exception(__('you do not have enough coins for creating a room'));
            }

            $amountBefore =  $user->di;
            UserCoinLogHelper::logByType(
                $user->id,
                -abs($paidRoomAmount->value),
                $amountBefore,
                UserCoinLogType::CREATE_ROOM,
            );

            $user->di = $user->di - $paidRoomAmount->value;
            $user->save();
        }

        $room = $this->repository->create($data);

        if ($request->type) {
            $room->type = $request->type;
            if ($request->type == 'live') {
                $room->is_live = true;
            }
        }

        // if ($request->hasFile('room_cover')) {
        //     $room->room_cover = WebPHelper::uploadWebp(
        //         $request->file('room_cover'),
        //         'rooms',
        //         'room_cover',
        //         async: true  // Convert to WebP asynchronously
        //     );


        // } 

       

        if ($request->hasFile('room_cover')) {
            // Validate image
            $validation = Common::validateMedia($request->file('room_cover'), 'room');
            if (!$validation['valid']) {
                throw new \Exception($validation['error']);
            }

            $room->room_cover = Common::uploadOptimized(
                'rooms',
                $request->file('room_cover'),
                'room',
                Room::class,
                $room->id,
                'room_cover'
            );
        } else {
            $room->room_cover = $request->room_cover;
        }

        // Server-side letter cover: never leave a room/live without a real image.
        if (empty($room->room_cover)) {
            $generated = app(\App\Services\LetterAvatarService::class)
                ->generate('rooms', $room->room_name, $room->id);
            if ($generated) {
                $room->room_cover = $generated;
            }
        }

        if (!is_null($request->mode)) {
            $this->changeModeCreateRoom($request, $request->mode, $room);
        }

        $this->repository->updateRoomUser($room);
        return $room;
    }


    public function findRoomUser($userId)
    {
        return $this->repository->findRoomUser($userId);
    }

    public function findAudioRoomUser($userId)
    {
        return $this->repository->findAudioRoomUser($userId);
    }

    public function findRoomUserByType($userId, $type)
    {
        return $this->repository->findRoomUserByType($userId, $type);
    }


    public function createPrivetMessage($fromUserId, $toUserId, $message, $price)
    {
        return $this->repository->createPrivetMessage($fromUserId, $toUserId, $message, $price);
    }



    public function privateComment($toUserId, $message, $ownerId, $fromUser)
    {
        $toUser = $this->userRepository->findById($toUserId);

        $price = Common::getConfig('private_comment_price') ?? 100;

        $room = $this->findRoomUser($ownerId);
        if (!$room)  throw new \Exception(__('room not founded'));

        //validate if user have coins enough or not
        if ($fromUser->di < $price) throw new \Exception(__('not enough coins'));

        $this->createPrivetMessage($fromUser->id, $toUserId, $message, $price);
        $fromUser->di -= $price;
        $fromUser->save();

        return [$toUser, $price];
    }
    public function findRoom($id)
    {
        return $room = $this->repository->findRoom($id);
    }

    public function findRoomForShow($id)
    {
        return $this->repository->findRoomForShow($id);
    }

    public function disableWriting($roomId)
    {
        $room = $this->findRoom($roomId);
        if (!$room) {
            throw new \Exception(__('api_responses.room_not_found'));
        }

        $room->writing_disabled = !$room->writing_disabled;
        $this->repository->updateRoomUser($room);
        return $room;
    }

    public function changeRoomImage($ownerId)
    {
        $room =    $this->findRoomUser($ownerId);

        if (!$room) throw new \Exception(__('room not found'));
        $room->enableSaving = false;
        $room->is_pk_custom = true;
        $this->repository->updateRoomUser($room);
        return $room;
    }

    public function adminOwner($request, $user)
    {
        $adminOnlyTypes = ['clear_chat', 'music'];
        $room = $this->findRoom($request->room_id);

        if (!$room) {
            throw new \Exception(__('room not found'));
        }

        $isRoomOwner = $user->id === $room->uid;

        if (in_array($request->type, $adminOnlyTypes)) {
            $adminIds = array_filter(explode(',', (string) $room->room_admin));

            // Clean whitespace and remove empty strings
            $adminIds = array_unique(array_map('trim', $adminIds));

            return in_array($user->id, $adminIds) || $isRoomOwner;
        }

        return $isRoomOwner;
    }


    public function getFirstRoomOwner($ownerId)
    {
        return $this->giftLogRepository->getFirstRoomByOwnerId($ownerId);
    }

    /**
     * @throws \Exception
     */
    public function roomAdmins($id)
    {
        $room = $this->repository->findRoomId($id, true);
        if (!$room) throw new \Exception(__('room not found'));

        if (empty($room->room_admin)) return collect();

        $adminIds = explode(',', $room->room_admin);
        return $this->userRepository->getAdmins($adminIds);
    }

    public function changePasswordRoom($ownerId = null, $roomId = null)
    {
        $room = $roomId
            ? $this->repository->findById($roomId)
            : $this->repository->findRoomUserEnableAudio($ownerId);
        if ($room) {
            $room->room_pass = '';
            $this->repository->updateRoomUser($room);
            return  $room;
        }
        return  $room;
    }

    public function quiteRoom($ownerId = null, User $user, $roomId = null)
    {
        $room = $roomId
            ? $this->repository->findById($roomId)
            : $this->repository->findRoomUserEnableAudio($ownerId);
        if (!$room)  throw new \Exception(__("Room not found for this owner."));
        // Charisma is client-side seat state now: no backend reset on quit.

        if (isset($room->microphone)) {

            $microphones = explode(',', $room->microphone);
            if (in_array($user->id, $microphones)) {
                UserHandling::calcTime($user->id);
            }
        }

        $res                = Common::quit_hand($ownerId, $user->id);
        $visitorIdsList   = explode(',', $res);

        $user->now_room_uid = 0;
        $user->save();
        if ($room->uid == $user->id && Schema::hasColumn('rooms', 'is_live') && $room->type !== 'audio') {

            $room->update(['is_live' => false]);
        }
        /* if ($room->count_room_socket > 0) {
            $room->count_room_socket -= 1;
        } else {
            $room->count_room_socket = 0;
        }*/




        if ($room->is_afk == null && $room->room_admin == null) {
            $room->is_afk = 0;
        }

        if ($user->id == $ownerId && $room->room_admin == null) {
            $room->is_afk = 0;
        }
        $this->repository->updateRoomUser($room);

        return [$visitorIdsList, $room->id];
    }

    public function quiteRoom2($ownerId = null, User $user, $roomId = null)
    {
        $room = $roomId
            ? $this->repository->findById($roomId)
            : $this->repository->findRoomUserEnableAudio($ownerId);
        if (!$room)  throw new \Exception(__("Room not found for this owner."));
        // Charisma is client-side seat state now: no backend reset on quit.

        //        if (isset($room->microphone)) {
        //
        //            $microphones = explode(',', $room->microphone);
        //            if (in_array($user->id, $microphones)) {
        //                UserHandling::calcTime($user->id);
        //            }
        //        }

        $micUserIds = $room->microphones()->pluck('user_id')->filter()->all();
        if (in_array($user->id, $micUserIds, true)) {
            UserHandling::calcTime($user->id);
        }

        $res = Common::quit_hand_2($ownerId, $user->id);
        $visitorIdsList = explode(',', $res);

        $user->now_room_uid = 0;
        $user->save();

        $taskStreamRoom = $room->taskStreamRoom()->first();
        if ($taskStreamRoom) {
            app(TaskStreamService::class)->leave(['task_stream_id' => $taskStreamRoom->task_stream_id]);
        }

        if ($room->uid == $user->id && Schema::hasColumn('rooms', 'is_live') && $room->type !== 'audio') {
            $room->update(['is_live' => false]);
        }

        if ($room->is_afk == null && $room->room_admin == null) {
            $room->is_afk = 0;
        }

        if ($user->id == $ownerId && $room->room_admin == null) {
            $room->is_afk = 0;
        }
        $this->repository->updateRoomUser($room);

        return [$visitorIdsList, $room->id];
    }

    public function roomUsers($request,)
    {
        $room = $this->findRoomUser($request->owner_id);
        if (!$room) throw new \Exception('Room not found');
        $currentPage = $request->page ?? 1;
        $visitors = null;

        if ($request->has('users') && $currentPage == 1) {
            $room->enableSaving      = false;
            $visitors      = $request->users ?? '';
        }

        $roomAdmin   = $room->room_admin ?? '';

        // Use repository to get visitor IDs if not provided in request
        if (isset($visitors)) {
            $roomVisitor = explode(',', $visitors);
        } else {
            $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
            $roomVisitor = $visitorRepo->getVisitorIds($room->id)->toArray();
        }

        $roomAdmin        = explode(',', $roomAdmin);
        $roomAdminActive  = array_intersect($roomVisitor, $roomAdmin);

        $roomVisitorArray = array_diff($roomVisitor, array_merge($roomAdminActive, [$room->uid . '']));
        $users = $this->userRepository->usersRoom($roomAdminActive);

        $usersCount        = count($roomVisitor);
        $countPrimary      = $users->count(['users.id']);
        $perPage           = 10;
        //        $diffCountWithPage = $countPrimary - ($perPage * $currentPage);

        $users = $users->paginate($perPage);

        if ($currentPage == 1 && in_array($room->uid, $roomVisitor)) {
            $allData[] = $room->owner;
            $allData   = array_merge($allData, $users->items());
        } else {
            $allData = $users->items();
        }

        $allData = collect($allData);
        $diffCountWithPage = $this->getDiffCountWithPage($countPrimary, $perPage, $currentPage);

        if ($diffCountWithPage < 0) {
            [$limit, $offset] = $this->getNewLimitAndOffset($countPrimary, $perPage, $currentPage);

            $anotherData =  $this->userRepository->anotherUserRoom($roomVisitorArray, $limit, $offset);

            $allData = $allData->merge($anotherData);
        }
        return [$allData, $roomAdminActive];
    }


    public function getRoomsForGame($gameId)
    {
        return $this->repository->getRoomsByGameId(gameId: $gameId, with: ['game', 'boxUse' => fn($q) => $q->where('not_used_num', '>=', 1), 'backgroundImage']);
    }

    public function changeModeCreateRoom($request, $currentMode, Room $room)
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

    public function changeMode($request, $currentMode)
    {
        $user = request()->user();
        $roomId = $request->room_id;
        $room = $roomId
            ? $this->repository->findById($roomId)
            : $this->repository->findRoomUserEnableAudio($request->owner_id);

        if (!$room) return Common::apiResponse(0, 'not found', null, 404);
        if ($user->id != $room->uid) return Common::apiResponse(0, __('you don not have permission'), null, 404);
        $youtubeStatus =  (bool)(getSettingCash('youtube_status') ?? true);
        if ($currentMode == 5 && $youtubeStatus === false) return Common::apiResponse(0, __('this feature stopped'), null, 404);
        $lastMode = $room->mode;
        $room->mode = $currentMode;
        $room->save();
        $this->resetRoomMicrophones($room);
        $jsons = [];
        $map = [];
        $mode = '';
        try {
            if ($currentMode == '1') {
                $mode = 'party';
            } elseif ($currentMode == '2') {
                $mode = 'seats12';
            } elseif ($currentMode == '6') {
                $mode = 'seats2';
            } elseif ($currentMode == '7') {
                $mode = 'seats22';
            } elseif ($currentMode == '9') {
                $mode = 'seats8';
            } elseif ($currentMode == '5') {
                $mode = 'cinema';
                //            $json = $this->changeBackground($room, $request->owner_id, 'custom_image/back-black.png');
                //            $jsons[] = $json;
            } elseif ($currentMode == '4') {
                $mode = 'game';
                if (!$request->game_id) return Common::apiResponse(0, 'please send game_id', null, 404);
                $game = AllGame::find($request->game_id);
                if (!$game) return Common::apiResponse(false, 'this game does not exists');

                $room->game_id = $request->game_id;
                $room->save();
                $map['game_url'] = $game->mini_url;
            } elseif ($currentMode == '8') {
                $mode = 'eight';
            } else {
                $mode = 'topCenter';
            }
        } catch (\Throwable $e) {
            return Common::apiResponse(0, $e->getMessage());
        }
        $ms   = [
            'messageContent' => array_merge($map, ['message' => 'roomMode', 'mode' => $mode])
        ];
        $json = json_encode($ms);
        $jsons[] = $json;


        $jsons[] = $this->changeBackground($room, $room->uid, (new RoomService())->getRoomBackground($room));

        $promises = Common::sendToStream3('SendCustomCommand', $room->id, $request->user()->id, $jsons);
        try {
            Utils::unwrap($promises);
        } catch (\Throwable $e) {
            // \Log::error("changeMode: stream send failed - " . $e->getMessage());
        }


        return Common::apiResponse(1, 'done', null, 201);
    }

    public function resetRoomMicrophones($room)
    {
        RoomMicrophone::where('room_id', $room->id)->delete();
        $mic = RoomMicrophone::create([
            'room_id'  => $room->id,
            'user_id'  => $room->uid,
            'position' => 0,
            'status'   => 1,
        ]);
    }
    public function getRoomBackground(?Room $room)
    {
        if ($room == null) return '';
        return $room->final_room_image ?? '';
    }

    // public function changeMode($request, $currentMode, $userId = null)
    // {
    //     $room =  $this->findRoomUser($request->owner_id ?? $userId);
    //     if (!$room) return Common::apiResponse(0, 'not found', null, 404);
    //     //get last mode of rooms to if is cinema mode and change it update room background
    //     $lastMode = $room->mode;
    //     $room->mode = $currentMode;
    //     $room->save();
    //     $jsons = [];
    //     $map = [];
    //     if ($currentMode == '1') {
    //         $mode = 'party';
    //     } elseif ($currentMode == '2') {
    //         $mode = 'seats12';
    //     } elseif ($currentMode == '5') {
    //         $mode = 'cinema';
    //         //            $json = $this->changeBackground($room, $request->owner_id, 'custom_image/back-black.png');
    //         //            $jsons[] = $json;
    //     } elseif ($currentMode == '4') {
    //         $mode = 'game';
    //         if (!$request->game_id) return Common::apiResponse(0, 'please send game_id', null, 404);
    //         $game = AllGame::find($request->game_id);
    //         if (!$game) return Common::apiResponse(false, 'this game does not exists');

    //         $room->game_id = $request->game_id;
    //         $room->save();
    //         $map['game_url'] = $game->mini_url;
    //     } else {
    //         $mode = 'topCenter';
    //     }
    //     $ms   = [
    //         'messageContent' => array_merge($map, ['message' => 'roomMode', 'mode' => $mode])
    //     ];
    //     $json = json_encode($ms);
    //     $jsons[] = $json;
    //     //        Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);

    //     if ($lastMode == '3' && $currentMode != '3') {
    //         $jsons[] = $this->changeBackground($room, $request->owner_id, (new RoomService())->getRoomBackground($room));
    //     }
    //     $promises = Common::sendToStream3('SendCustomCommand', $room->id, $request->user()->id, $jsons);

    //     try {
    //         Utils::unwrap($promises);
    //     } catch (\Throwable $e) {
    //     }
    //     return Common::apiResponse(1, 'done', null, 201);
    // }

    public function changeModeMic($request, $currentMode)
    {
        $room =  $this->findRoomUser($request->owner_id);
        if (!$room) return Common::apiResponse(0, 'not found', null, 404);
        //get last mode of rooms to if is cinema mode and change it update room background
        $lastMode = $room->mode;
        $room->mode = $currentMode;
        $room->save();
        $jsons = [];
        $map = [];
        $mode = $currentMode;
        $ms   = [
            'messageContent' => array_merge($map, ['message' => 'roomMode', 'mode' => $mode])
        ];
        $json = json_encode($ms);
        $jsons[] = $json;

        $promises = Common::sendToStream3('SendCustomCommand', $room->id, $request->user()->id, $jsons);

        try {
            Utils::unwrap($promises);
        } catch (\Throwable $e) {
        }
        return Common::apiResponse(1, 'done', null, 201);
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
        //        Common::sendToStream('SendCustomCommand', $room->id, $owner_id, $json);
        return $json;
    }

    public function userRooms($userId)
    {
        return  $this->repository->roomUsers($userId);
    }

    /**
     * @throws \Exception
     */
    public function commentStatus($roomId, $request): bool
    {
        $room = $this->repository->findRoom($roomId);

        if (!$room)  throw new \Exception(__('room not founded'));

        if (auth()->id() != $room->uid && ! in_array(auth()->id(), $room->admins)) {
            throw new \Exception(__('you don not have permission'));
        }

        $room->update(['is_comment_closed' => $request['status']]);

        return $room->is_comment_closed;
    }

    public function index2()
    {
        return $this->countryRepository->countryGet();
    }

    public function update($request, $id)
    {
        $room = $this->repo->find($id);
        if (!$room) {
            return Common::apiResponse(false, 'Room not found', null, 404);
        }
        if ($room->uid != $request->user()->id && !in_array($request->user()->id, explode(',', $room->room_admin))) {
            return Common::apiResponse(false, 'not allowed', null, 403);
        }
        if ($request->room_name) {
            $room->room_name = $request->room_name;
        }

        if ($request->hasFile('room_cover')) {
            $validation = Common::validateMedia($request->file('room_cover'), 'room');
            if (!$validation['valid']) {
                return Common::apiResponse(false, $validation['error'], null, 422);
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

        if ($request->room_pass) {
            $room->room_pass = $request->room_pass;
        }

        $RoomCategoryides = RoomCategory::where('enable', 1)->pluck('id');

        if ($request->room_type !== null) {
            // if (!RoomCategory::query()->where('id', $request->room_type)->where('enable', 1)->exists()) return Common::apiResponse(0, 'type not found', null, 404);
            if (!in_array($request->room_type, $RoomCategoryides)) {
                return Common::apiResponse(0, 'Type not found', null, 404);
            }
            $room->room_type = $request->room_type;
        }

        if ($request->room_class !== null) {
            if (!in_array($request->room_class, $RoomCategoryides)) {
                return Common::apiResponse(0, 'Type not found', null, 404);
            }
            // if (!RoomCategory::query()->where('id', $request->room_class)->where('enable', 1)->exists()) return Common::apiResponse(0, 'class not found', null, 404);
            $room->room_type = $request->room_type;
        }


        $background_me = '';
        if ($request->room_background) {
            /*if (!Background::query ()->where ('id',$request->room_background)->where ('enable',1)->exists ()){
                return Common::apiResponse (0,'background not found',null,404);
            }*/
            if ($request->change == 'app') {
                Common::backgroundCount($room->room_background, $request->room_background);
                $room->room_background = $request->room_background;
                RequestBackgroundImage::query()->where('owner_room_id', $room->uid)->where('status', 1)->update(['status' => 3]);
            }
            if ($request->change == 'me') {
                RequestBackgroundImage::query()->where('owner_room_id', $room->uid)->where('id', '!=', $request->room_background)->where('status', 1)->update(['status' => 3]);
                $background_update         =
                    RequestBackgroundImage::where('id', $request->room_background)->first();
                $background_update->status = 1;
                $background_update->save();
                $background_me         = $background_update->img;
                Common::backgroundCount($room->room_background, 0);
                $room->room_background = null;
            }
        }

        // Cover can never be removed: regenerate the letter cover if it is empty.
        if (empty($room->room_cover)) {
            $generated = app(\App\Services\LetterAvatarService::class)
                ->generate('rooms', $room->room_name, $room->id);
            if ($generated) {
                $room->room_cover = $generated;
            }
        }

        $room->save();
        $request['owner_id'] = $room->uid;

        $data               = [
            "messageContent" => [
                "message"   => "changeBackground",
                "imgbackground" => $room->room_background ?: $background_me,
                "roomIntro" => $room->room_intro ?: "",
                "roomImg" => $room->room_cover ?: "",
                "room_type" => @$room->myType->name ?: "",
                "room_name" => @$room->room_name ?: ""
            ]
        ];
        $json               = json_encode($data);
        $res                = Common::sendToStream('SendCustomCommand', $room->id, $request->user()->id, $json);
        $request->is_update = true;
        return true;
    }
}
