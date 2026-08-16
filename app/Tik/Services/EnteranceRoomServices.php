<?php

namespace App\Tik\Services;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Http\Resources\Api\V1\EnterRoomCollection;
use App\Http\Resources\Api\V1\EnterRoomLiveCollection;
use App\Jobs\SendNotificationToAllFollowers;
use App\Models\EnteredRoom;
use App\Models\Room;
use App\Models\RoomMicrophone;
use App\Models\RoomVisitor;
use App\Models\User;
use App\Tik\Repositories\EnteranceRoomRepository;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Events\Chat;
use Modules\Chat\Events\Conversation;
use Modules\Chat\Events\OpenChat;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\CP\Entities\CpRoomHistory;
use Modules\RoomCup\Helpers\RoomCupHelper;
use Modules\TaskStream\Services\TaskStreamService;

class EnteranceRoomServices
{
    protected $roomRepository;
    protected $userRepository;
    protected $enteranceRoomRepository;

    public function __construct(RoomRepository $roomRepository, UserRepository $userRepository)
    {
        $this->roomRepository = $roomRepository;
        $this->userRepository = $userRepository;
    }

    public function enteranceRoom($modelClass)
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException('Invalid model class');
        }

        $modelInstance = new $modelClass;
        $enternaceRepo = new EnteranceRoomRepository($modelInstance);
        return $enternaceRepo;
    }


    //////////////////////////////////////////////////////room visitors//////////////////////////////////////////
    public function updateRoomCountFromStream(Request $request)
    {
        //        $app_secert='a23b121a64ee9fab4567a2d75d00269d';
        //        if (!$this->checkSignature($app_secert,$request->signature, $request->timestamp, $request->nonce)) {
        //            return response()->json(['status' => 'success'], 200);
        //        }
        $event = $request->event;
        $roomId = $request->room_id;
        $userId = $request->user_account;

        /** @var Room $room */
        $room = Room::select(['id', 'uid', 'count_room_socket', 'charizma_status', 'microphone'])->find($roomId);
        $user = User::find($userId);

        if (!$room || !$user) {
            return response()->json(['status' => 'Webhook received but room or user not found']);
        }

        // Single write per event via the repository (single source of truth).
        $this->withDeadlockRetry(function () use ($event, $room, $user) {
            $this->updateRoomVisitorsBasedOnEvent($event, $room, $user->id);
        });

        if ($event == 'room_login') {
            $user->now_room_uid = $room->id;
        } elseif ($event == 'room_logout'  && $room->uid == $user->now_room_uid) {
            $user->now_room_uid = 0;
        }

        if ($event == 'room_logout') {
            $roomIsAlive = app(\App\Repositories\RoomVisitorRepository::class)
                ->getVisitorCount($room->id) > 0;

            if ($user->id === $room->uid) {
                $room->is_afk = 0;
            }
            $this->handleLeaveCp($user, $room, $roomIsAlive);

            // Charisma is client-side seat state now: the leaving user's seat
            // charisma resets on the client — no backend reset/broadcast here.
        }

        $user->save();
        $room->save();

        return response()->json(['status' => 'Webhook processed successfully']);
    }

    public function updateRoomCountFromStream2(Request $request)
    {
        $event = $request->event;
        $roomId = $request->room_id;
        $userId = $request->user_account;

        /** @var Room $room */
        $room = Room::select(['id', 'uid', 'count_room_socket', 'charizma_status', 'microphone'])->find($roomId);
        $user = User::find($userId);

        if (!$room || !$user) {
            return response()->json(['status' => 'Webhook received but room or user not found']);
        }

        $taskStreamRoom = $room->taskStreamRoom()->first();
        if ($taskStreamRoom) {
            app(TaskStreamService::class)->leave(['task_stream_id' => $taskStreamRoom->task_stream_id]);
        }

        // Single write per event via the repository (single source of truth).
        // Wrapped in a deadlock-retry guard for the critical webhook path.
        $this->withDeadlockRetry(function () use ($event, $room, $user) {
            $this->updateRoomVisitorsBasedOnEvent2($event, $room, $user->id);
        });

        if ($event == 'room_login') {
            $user->now_room_uid = $room->id;
        } elseif ($event == 'room_logout'  && $room->uid == $user->now_room_uid) {
            $user->now_room_uid = 0;
        }

        if ($event == 'room_logout') {
            // Guard: if the room is empty after this leave, the stream engine has already
            // auto-destroyed it. Sending SendCustomCommand to a dead room is a
            // guaranteed Code 104 that reaches no one — so skip the network
            // send while keeping all DB-side cleanup (CpRoomHistory + charisma
            // reset) which is what actually matters.
            $roomIsAlive = app(\App\Repositories\RoomVisitorRepository::class)
                ->getVisitorCount($room->id) > 0;

            if ($user->id === $room->uid) {
                $room->is_afk = 0;
            }
            $this->handleLeaveCp($user, $room, $roomIsAlive);

            // Charisma is client-side seat state now: the leaving user's seat
            // charisma resets on the client — no backend reset/broadcast here.
        }

        $user->save();
        $room->save();

        return response()->json(['status' => 'Webhook processed successfully']);
    }

    public function handleLeaveCp($user, $room, bool $roomIsAlive = true)
    {
        $userId = $user->id;
        $this->removeUserCpInRoom($userId);
        return $this->sendCpLovelyMessage($room, $user, $roomIsAlive);
    }
    public function removeUserCpInRoom(mixed $userId): void
    {
        CpRoomHistory::where("user_one_id", $userId)
            ->orWhere("user_two_id", $userId)->delete();
    }

    public function sendCpLovelyMessage($room, $user, bool $roomIsAlive = true)
    {
        $cpRoomHistories = CpRoomHistory::where("room_id", $room->id)->get(['index1', 'index2']);
        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        $json = $this->cpMapJson($indices);

        // Skip the network send when the room is already empty/destroyed (dead
        // room → Code 104). DB cleanup above always runs.
        if (!$roomIsAlive) {
            return;
        }

        // FromUserId = room->uid (room owner, always present on the loaded room
        // row) instead of the leaving user's id, so the command is attributed
        // to a stable sender that exists for the room's lifetime.
        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, $json);
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
    /*public function updateRoomCountFromStream(Request $request)
    {
        $event = $request->event;
        $roomId = $request->room_id;
        $userId = $request->user_account;

        $room = $this->roomRepository->findRoomUser($roomId);
        $user = $this->userRepository->findById($userId);

        if (!$room || !$user) {
            return response()->json(['status' => 'Webhook received but room or user not found']);
        }

        $visitors = $this->updateRoomVisitorsBasedOnEvent($event, $room, $user->id);

        if ($event == 'room_login') {
            $this->addUserToVisitors($room->id, $user->id);
            $user->now_room_uid = $room->uid;
        } elseif ($event == 'room_logout' && $room->uid == $user->now_room_uid) {
            $user->now_room_uid = 0;
        }
        if ($event == 'room_logout') {
            $this->removeUserFromVisitors($room->id, $user->id);
        }

        if ($event == 'room_logout' && $room->charizma_status) {
            $this->handleCharismaStatusOnLogout($room, $user, $request->owner_id);
        }

        $this->userRepository->updateUser($user);

        $enteranceRepo = $this->enteranceRoom(RoomVisitor::class);
        $count = $enteranceRepo->countVisitors($room->id);
        $this->roomRepository->updateRoom($room, [
            'count_room_socket' => $count,
            'room_visitor' => implode(",", $visitors)
        ]);

        return response()->json(['status' => 'Webhook processed successfully']);
    }*/

    /**
     * Run a critical visitor-write closure with deadlock (MySQL 1213) retry.
     *
     * Same pattern as SanctumTokenService: MAX_RETRIES=3 with exponential
     * backoff 100/200/400ms. Acts as the final safety net for the rare
     * remaining race after the atomic upsert + UNIQUE constraint; never throws
     * so the webhook still returns 200.
     */
    private function withDeadlockRetry(callable $operation): void
    {
        $maxRetries = 3;
        $baseDelayMs = 100;
        $retries = 0;

        while (true) {
            try {
                $operation();
                return;
            } catch (\Throwable $e) {
                $isDeadlock = str_contains($e->getMessage(), '1213')
                    || str_contains($e->getMessage(), 'Deadlock');

                if (!$isDeadlock) {
                    Log::error('Room visitor write failed', ['error' => $e->getMessage()]);
                    return;
                }

                $retries++;
                if ($retries >= $maxRetries) {
                    Log::warning('Room visitor write deadlock after retries', ['error' => $e->getMessage()]);
                    return;
                }

                // Exponential backoff: 100ms, 200ms, 400ms
                usleep($baseDelayMs * (2 ** ($retries - 1)) * 1000);
            }
        }
    }

    private function updateRoomVisitorsBasedOnEvent($event, $room, $userId)
    {
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);

        if ($event == 'room_login') {
            $visitorRepo->addVisitor($room->id, $userId);
        } elseif ($event == 'room_logout') {
            UserHandling::calcTime($userId);
            $this->updateMicrophone($room->uid, $userId);
            $visitorRepo->removeVisitor($room->id, $userId);
        }

        // Return visitor IDs for backward compatibility
        return $visitorRepo->getVisitorIds($room->id)->toArray();
    }

    private function updateRoomVisitorsBasedOnEvent2($event, $room, $userId)
    {
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);

        if ($event == 'room_login') {
            $visitorRepo->addVisitor($room->id, $userId);
        } elseif ($event == 'room_logout') {
            UserHandling::calcTime($userId);
            $this->updateMicrophone2($room->uid, $userId);
            $visitorRepo->removeVisitor($room->id, $userId);
        }

        // Return visitor IDs for backward compatibility
        return $visitorRepo->getVisitorIds($room->id)->toArray();
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
    ///////////////////////////////


    public function enterRoom($user, $request, $room_pass, Room $room)
    {
        $owner_id = $room->uid;
        if ($request->sub_type == 'random') {
            $owner_id = $this->roomRepository->randomOwner();
        }

        // if owner id not path throw error
        if (!$owner_id) return Common::apiResponse(0, 'not found', null, 404);
        //check if this user in black-list
        try {
            $black_list = Common::getUserBlackListInRoom($owner_id, $user->id);
            if ($black_list) return Common::apiResponse(false, __('You have been blocked by the other party'), null, 422);
        } catch (\Exception $e) {
            Log::error('Failed to check black list', ['user_id' => $user->id, 'owner_id' => $owner_id, 'error' => $e->getMessage()]);
            // Continue even if black list check fails
        }


        if (!$room) return Common::apiResponse(false, 'No room yet, please create first', null, 404);
        // if(($room->count_room_socket == 0 ) && $room->uid != $user_id && $room->pin != 1 )return Common::apiResponse(false, __('api_responses.closedRoom'), null, 402);
        if ($room->room_status == 2) {
            return Common::apiResponse(0, __('room_closed'));
        }
        // Room ban / timed kick (TYPE 2 + TYPE 3) via the authoritative repo — same
        // gate as the live path. Replaces the old room_black string parse which
        // leaked permanent bans ($arr[2] undefined) and scrubbed them on entry.
        if ($banResponse = $this->checkRoomBanOrKick($room, $user)) {
            return $banResponse;
        }


        if ($room->room_pass &&  $owner_id != $user->id) {
            if (!$room_pass)  return Common::apiResponse(false, __('The room is locked, please enter the password'), null, 409);
            if ($room->room_pass != $room_pass) return Common::apiResponse(false, __('Password is incorrect, please re-enter'), null, 410);
        }

        $this->deleteOldRoomMic($user, $room);

        if ($user->id == $owner_id) {
            $room->is_afk = 1;
            // Mirror of exit-room: owner re-entering a non-audio room resumes the
            // broadcast, otherwise is_live stays false forever after the first exit
            // and the room never shows in /rooms/live-rooms again.
            if (Schema::hasColumn('rooms', 'is_live') && $room->type !== 'audio') {
                $room->is_live = true;
            }
            $room->save();
            if ($room->count_room_socket == 0) {

                    dispatch(new SendNotificationToAllFollowers($room->uid))->onQueue('notification_heavy');

            }
        }


        $room_info = (new EnterRoomCollection($room, $user->id));

        $room_info = $room_info->toArray($request);


        $this->updateRoom($user->id, $owner_id, $room);
        $this->enterTheRoomCreateOrUpdate($user->id, $owner_id, $room->id);
        // $this->updateRoomVisitor($user_id, $owner_id, $room);

        //send to stream
        $user->enableSaving = false;
        $user->now_room_uid = (int)$room->id;
        $user->save();

        if (config('app.env') != "production") {
            try {
                RoomVisitor::firstOrCreate([
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create room visitor', ['user_id' => $user->id, 'room_id' => $room->id, 'error' => $e->getMessage()]);
                // Continue even if visitor creation fails
            }
        }

        return Common::apiResponse(true, '', $room_info);
    }

    public function deleteOldRoomMic($user, $room)
    {
        RoomMicrophone::where('user_id', $user->id)->where('room_id','!=', $room->id)->delete();
        $partner = $user->lovelyRelations()
            ->with(['userOne', 'userTwo'])
            ->first()?->partner;

        CpRoomHistory::where("user_one_id", $user->id)
            ->orWhere("user_two_id", $user->id)->delete();

        $json = $this->cpMapJson([]);
        if ($partner) {
            // Engine HTTP must never block the entry critical path; the partner
            // CP-display update is cosmetic and tolerates queue latency.
            $partnerRoomUid = @$partner->now_room_uid;
            $partnerId = $partner->id;
            dispatch(function () use ($partnerRoomUid, $partnerId, $json) {
                Common::sendToStream('SendCustomCommand', $partnerRoomUid, $partnerId, $json);
            })->onQueue('default');
        }
    }



    private function updateRoom($user_id, $owner_id, Room &$room)
    {
        // $this->updateRoomVisitors($user_id, $owner_id, $room);

        if ($room->charizma_status && ($room->charizma_timestamp  + 86400) < now()->timestamp) {
            $room->charizma_timestamp = null;
            $room->charizma_status = false;
            // Charisma is client-side now: cue clients to hide/zero the seat
            // charisma (no backend storage to clear).
            Common::sendToStream('SendCustomCommand', $room->id, $room->uid, json_encode([
                'messageContent' => ['message' => 'closeCharisma'],
            ]));
        }
        if ($room->uid == $user_id) {
            $room->is_live = true;
        }

        $room->save();
    }
    private function enterTheRoomCreateOrUpdate($user_id, $owner_id, $room_id)
    {
        $timezone = Common::timeZone();

        EnteredRoom::query()->updateOrCreate(
            [
                'uid' => $user_id,
                'ruid' => $owner_id,
                'rid' => $room_id
            ],
            [
                'entered_at' => now($timezone)
            ]
        );
        // DISTINCT COUNT + lockForUpdate on the shared per-room TotalRoomGift row
        // serializes concurrent entries to the same room; the response never uses
        // the result. Recompute on the queue; afterCommit guarantees the
        // EnteredRoom upsert above is visible to the job.
        dispatch(function () use ($room_id) {
            RoomCupHelper::updateRoomVisitors((int) $room_id);
        })->onQueue('default')->afterCommit();
    }


    public function makeRequestInviteRoom($user, $request)
    {

        $tokens_notfacion = [];

        $room = Room::where('uid', '=', $request->owner_id)->first();
        if (!$room) throw new Exception('room not found');

        $chatRoom = ChatRoom::BetweenUsers($user->id, $request->user_id)->first();


        if (!$chatRoom) {

            $chatRoom = ChatRoom::create([
                'user_id' => $user->id,
                'user_id2' => $request->user_id
            ]);

            $user->current_room_chat = $chatRoom->id;
            $user->save();
        }


        $user2 = User::find($request->user_id);
        if (!$user2) throw new Exception('user not found');


        $data = [
            'title' => __('I invite you to enter my room'),
            'room_owner_id' => intval($request->owner_id),
            'room_id' => intval($room->id),
            'status' => 0
        ];

        $key = env('MESSAGE_KEY');

        $message = json_encode($data);

        $chatMessageData = [
            'chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'room_owner_id' => intval($request->owner_id),
            'room_id' => intval($room->id),
            'message' => __('I invite you to enter my room'),
            'type' => 'invite_room'
        ];

        if ($user2->online == 1 && $user2->current_room_chat == $chatRoom->id) {

            $chatMessageData['status'] = 'seen';
        } else if ($user2->online == 1) {
            $chatMessageData['status'] = 'received';
        }
        $chatMessage = ChatMessage::create($chatMessageData);

        if ($user2->is_logout != 1) {
            $tokens_notfacion[] = \DB::table('users')->where('id', $user2->id)->value('notification_id');
            $title = $user->name;
            $body = $message;
            $type = $message->type ?? 'text';
            Common::send_firebase_notification($tokens_notfacion, $title, $body, messageType: $type);
        }

        $message_resource = new ChatMessageResource($chatMessage);
        $room_resource =  new ChatRoomResource($chatRoom);
        if ($chatRoom->user_id == $user->id) {
            $chatuser = User::find($chatRoom->user_id2);
        } else {
            $chatuser = User::find($chatRoom->user_id);
        }

        try {
            event(new OpenChat($room_resource->toResponse(request())->getData()->data, $chatuser, $chatRoom));
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

        event(new Conversation($message_resource->toResponse(request())->getData()->data, $user2, $room_resource));

        event(new Chat($room_resource->toResponse(request())->getData()->data, $user2));

        return Common::apiResponse(1, 'تم الارسال  بنجاح');
    }




    public function enterLiveRoom($user, Request $request, $roomPass, Room $room)
    {
        $ownerId = $this->getOwnerId($request, $room);
        if (!$ownerId) {
            return Common::apiResponse(0, 'not found', null, 404);
        }

        if ($this->isUserBlocked($ownerId, $user->id)) {
            return Common::apiResponse(false, __('You have been blocked by the other party'), null, 422);
        }

        if ($error = $this->validateRoomStatus($room, $user)) {
            return $error;
        }

        if ($error = $this->checkRoomPassword($room, $roomPass, $ownerId, $user->id)) {
            return $error;
        }

        $this->handleOwnerLogic($room, $user);

        $this->recordLiveViewer($room, $user);

        $roomInfo = $this->prepareRoomInfo($room, $user, $request);

        $this->finalizeRoomEnter($user, $ownerId, $room);

        return Common::apiResponse(true, '', $roomInfo);
    }

    private function getOwnerId(Request $request, Room $room): ?int
    {
        return $request->type === 'random'
            ? $this->roomRepository->randomOwner()
            : $room->uid;
    }

    private function isUserBlocked(int $ownerId, int $userId): bool
    {
        try {
            return Common::getUserBlackListInRoom($ownerId, $userId);
        } catch (\Exception $e) {
            Log::error('Failed to check if user is blocked', ['owner_id' => $ownerId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            return false; // If check fails, assume not blocked to avoid blocking legitimate users
        }
    }

    private function validateRoomStatus(Room $room, $user)
    {
        if (!$room) {
            return Common::apiResponse(false, 'No room yet, please create first', null, 404);
        }

        if ($room->room_status == 2) {
            return Common::apiResponse(0, __('room_closed'));
        }

        if ($banResponse = $this->checkRoomBanOrKick($room, $user)) {
            return $banResponse;
        }

        return null;
    }

    /**
     * Authoritative room-ban / timed-kick gate (TYPE 2 + TYPE 3), used by BOTH the
     * audio and live entry paths. Reads the room_id-scoped, expiry- & permanent-aware
     * RoomBlacklist table (NOT the fragile legacy room_black string, which leaked
     * permanent bans and was never read on the live path). The room owner is never
     * banned from their own room. Returns the blocking response or null to allow.
     */
    private function checkRoomBanOrKick(Room $room, $user)
    {
        // Room kick/ban is enforced ENTIRELY by the UTD-Stream engine now: a
        // banned identity's token request returns 403 on (re)join and the kit
        // surfaces it via its own banned dialog, then leaves cleanly. We no
        // longer gate here on the backend RoomBlacklist table — doing so blocked
        // entry at the HTTP layer BEFORE the kit connected, which bypassed the
        // kit's clean banned-flow (and triggered a teardown crash) and could
        // keep blocking after an engine-side unban. The engine is the single
        // source of truth.
        return null;

        // (unreachable — kept for reference of the previous behaviour)
        if ((int) $user->id === (int) $room->uid) {
            return null;
        }

        $banRepo = app(\App\Repositories\RoomBlacklistRepository::class);
        if (!$banRepo->isBlacklisted($room->id, $user->id)) {
            return null;
        }

        $remaining = $banRepo->getTimeRemaining($room->id, $user->id); // seconds; null = permanent
        if ($remaining) {
            $minutes = max(1, (int) ceil($remaining / 60));
            // remaining_time (seconds) lets the app render a live countdown timer
            // with NO further server calls; kick_type tells it which copy to show.
            return Common::apiResponse(
                false,
                __('api.kickedTemporarily', ['minutes' => $minutes], 'ar'),
                ['remaining_time' => $remaining, 'kick_type' => 'kick'],
                422
            );
        }

        return Common::apiResponse(
            false,
            __('api.youAreBannedFromRoom', [], 'ar'),
            ['kick_type' => 'ban'],
            422
        );
    }

    private function checkRoomPassword(Room $room, ?string $roomPass, int $ownerId, int $userId)
    {
        if ($room->room_pass && $ownerId !== $userId) {
            if (!$roomPass) {
                return Common::apiResponse(false, __('The room is locked, please enter the password'), null, 409);
            }
            if ($room->room_pass !== $roomPass) {
                return Common::apiResponse(false, __('Password is incorrect, please re-enter'), null, 410);
            }
        }
        return null;
    }

    private function handleOwnerLogic(Room $room, $user): void
    {
        if ($user->id === $room->uid) {
            $room->is_afk = 1;
            // Mirror of exit-room: owner re-entering a non-audio room resumes the
            // broadcast (see liveRooms() filter on is_live + is_afk).
            if (Schema::hasColumn('rooms', 'is_live') && $room->type !== 'audio') {
                $room->is_live = true;
            }
            $room->save();

            if ($room->count_room_socket == 0) {
                try {
                    dispatch(new SendNotificationToAllFollowers($room->uid))->onQueue('notification_heavy');
                } catch (\Throwable $e) {
                    Log::warning('Failed to dispatch follower notification: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Counts $user once per broadcast in the cumulative unique-viewers stat.
     * O(1) on the hot entry path: one INSERT IGNORE on the (room_id, user_id)
     * unique key + a counter bump only when the row is actually new. The set
     * and the counter both reset at end-live
     * (RoomOccupancyReconciler::deactivateRoom), mirroring live_tap_totals.
     * The host is not a viewer of their own broadcast.
     */
    private function recordLiveViewer(Room $room, $user): void
    {
        if ((int) $user->id === (int) $room->uid) {
            return;
        }

        try {
            $inserted = DB::table('live_session_viewers')->insertOrIgnore([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            if ($inserted > 0) {
                DB::table('rooms')->where('id', $room->id)->increment('live_viewers_total');
                // Reflect the bump in the in-flight response without marking
                // the attribute dirty — the later $room->save() must not race
                // concurrent entries' increments with a stale value.
                $room->live_viewers_total = (int) ($room->live_viewers_total ?? 0) + 1;
                $room->syncOriginalAttribute('live_viewers_total');
            }
        } catch (\Throwable $e) {
            Log::warning('recordLiveViewer failed', [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function prepareRoomInfo(Room $room, $user, Request $request): array
    {
        $roomInfo = (new EnterRoomCollection($room, $user->id))->toArray($request);
        return $roomInfo;
    }

    private function finalizeRoomEnter($user, int $ownerId, Room $room): void
    {
        $this->updateRoom($user->id, $ownerId, $room);
        $this->enterTheRoomCreateOrUpdate($user->id, $ownerId, $room->id);

        $user->enableSaving = false;
        $user->now_room_uid = $ownerId;
        $user->save();

        if (config('app.env') !== "production") {
            RoomVisitor::firstOrCreate([
                'user_id' => $user->id,
                'room_id' => $room->id,
            ]);
        }
    }
}
