<?php

namespace Modules\Chat\Http\Controllers;

use App\Helpers\Common;
use App\helper\UserDataHelper;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
// use Modules\Chat\Entities\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\Chat\Http\Services\ChatRoomService;
use Modules\Chat\Http\Services\ChatService;

class ChatRoomController extends Controller
{

    public function __construct(public ChatRoomService $chatRoomService, public ChatService $chatService)
    {

    }

    public function users_list(){
        return Common::get_users_list();
    }
    public function inviteRoom(Request $request)
    {

        $userId = auth()->id();

        $data = [
            'message' => $request->message,
            'url'     => $request->image_url,
        ];

        $type = $request->type;
        $userIds = $request->users ? explode(',', $request->users) : [];
        $exceptIds = $request->except_ids ? explode(',', $request->except_ids) : [];

        $this->chatRoomService->handleInvite($data, $userId, $type, $userIds, $exceptIds);

        return Common::apiResponse(true, __('success'));
    }

    public function find_user(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1|max:255',
        ]);

        $data = $this->chatRoomService->findUsersByName($request->name);

        return response()->json($data);

    }

    public function index(Request $request)
    {
        $uuid = $request->keyword;
        $user = $request->user();

        // Cache for 2 minutes (120 seconds) to fix 4.3s latency reported in DevOps report
        // Short TTL because chat data changes frequently
        // Skip cache if searching by keyword (uuid)
        if ($uuid) {
            $response = $this->chatRoomService->getChatRooms($user, $uuid);
        } else {
            $response = \Cache::remember("chat_rooms_{$user->id}", 120, function () use ($user, $uuid) {
                return $this->chatRoomService->getChatRooms($user, $uuid);
            });
        }

        if (!$response['success']) {
            return response()->json($response['message'], $response['status']);
        }

        return Common::apiResponse(
            1,
            $response['message'],
            $response['data'],
            $response['status'],
            '',
            'chat'
        );
    }

    public function guestChat(Request $request)
    {
        $response = $this->chatRoomService->getGUestChatRooms($request->user());


        if (!$response['success']) {
            return response()->json($response['message'], $response['status']);
        }

        return Common::apiResponse(
            1,
            $response['message'],
            $response['data'],
            $response['status'],
            '',
            'request_chat'
        );
    }

    public function close_Chat(Request $request)
    {
        $user = User::find($request->user()->id);
        $user->current_room_chat  = null;
        $user->save();
        return 200;
    }

    public function userStatus($id)
    {
        $user = User::findOrFail($id);

        return response()->json(UserDataHelper::presence($user));
    }

    public function store(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $checkRoom = $this->chatRoomService->getOrCreateChatRoom($user, $request->user_id);

        // Clear cached chat rooms for both users so the list refreshes
        \Cache::forget("chat_rooms_{$user->id}");
        \Cache::forget("chat_rooms_{$request->user_id}");

        // Update user's current room chat

        $user->current_room_chat = $checkRoom->id;
        $user->update();
        // Retrieve and paginate chat messages
        $messages = $this->chatRoomService->getChatMessages($checkRoom->id, $request, $user);

        // Mark unread messages as seen
        $this->chatRoomService->markMessagesAsSeen($checkRoom, $user);

        // Find the second user in the chat room
        $user2 = $this->chatRoomService->getUserInChatRoom($checkRoom, $user);

        // Handle chat opening event
        $this->chatRoomService->handleChatOpenEvent($checkRoom, $user, $user2);

        // Check if room has a password
        $roomData = $this->chatRoomService->getRoomData($user2);

        // Prepare data for response
        $responseData = $this->chatRoomService->prepareResponseData($messages, $checkRoom, $user2, $roomData);

        return Common::apiResponse(1, 'successfully', $responseData, 200, '', 'messages');
    }

    /**
     * POST /api/Chat-room/ensure
     *
     * Lightweight get-or-create for a 1:1 room: resolve (or create) the chat_room
     * between the caller and {user_id} and return ONLY its id. This is the
     * offline-first client's room-resolution step — when a conversation is opened
     * from an entry point that doesn't already know the chat_room_id (profile,
     * friend picker, agency/search/moment/meet), the client calls this to obtain
     * the real id BEFORE running the seq-keyed sync (GET /v1/rooms/{id}/messages)
     * and the durable outbox send, so neither runs against room 0.
     *
     * Unlike store()/cursor() it does NOT page messages, mark-seen, broadcast an
     * OpenChat event or mutate current_room_chat — those are open-side effects the
     * modern client drives separately. Keeping it a thin id-resolver makes it cheap
     * to call on every open.
     */
    public function ensure(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        $chatRoom = $this->chatRoomService->getOrCreateChatRoom($user, $request->user_id);

        // A brand-new room changes the participants' chat lists; drop their caches
        // so the next list fetch reflects it (parity with store()).
        \Cache::forget("chat_rooms_{$user->id}");
        \Cache::forget("chat_rooms_{$request->user_id}");

        return Common::apiResponse(1, 'successfully', [
            'chat_room_id' => (int) $chatRoom->id,
        ], 200);
    }

    public function cursor(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => ['sometimes', 'string', Rule::in(['new', 'old'])],
            'message_id' => ['sometimes', 'integer']
        ]);

        $user = $request->user();
        $checkRoom = $this->chatRoomService->getOrCreateChatRoom($user, $request->user_id);

        $user->current_room_chat = $checkRoom->id;
        $user->update();
        $messages = $this->chatRoomService->getChatMessages($checkRoom->id, $request,$user);
     //   dd( $messages->toArray());

        $this->chatRoomService->markMessagesAsSeen($checkRoom, $user);

        $user2 = $this->chatRoomService->getUserInChatRoom($checkRoom, $user);

        $this->chatRoomService->handleChatOpenEvent($checkRoom, $user, $user2);

        $roomData = $this->chatRoomService->getRoomData($user2);

        $responseData = $this->chatRoomService->prepareResponseData($messages, $checkRoom, $user2, $roomData);

        return Common::apiResponse(1, 'successfully', $responseData, 200, '', 'messages');

    }
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //     ]);
    //     $user = $request->user();

    //     $check_room = ChatRoom::where(function ($query) use ($user, $request) {
    //         $query->where('user_id', $user->id)->where('user_id2', $request->user_id);
    //     })->orWhere(function ($query) use ($user, $request) {
    //         $query->where('user_id', $request->user_id)->where('user_id2', $user->id);
    //     })->first();

    //     if (!$check_room) {
    //         $check_room = new ChatRoom();
    //         $check_room->user_id = $user->id;
    //         $check_room->user_id2 = $request->user_id;
    //         $check_room->save();
    //     }
    //     $user->current_room_chat = $check_room->id;
    //     $user->update();

    //     $data = ChatMessage::where('chat_room_id', $check_room->id)
    //         ->with('reacts', 'albums')
    //         ->orderBy('id', 'desc')
    //         ->paginate(15);

    //     $total_unread = ChatMessage::where('chat_room_id', $check_room->id)
    //         ->where('user_id', '!=', $user->id)
    //         ->where('status', '!=', 'seen')
    //         ->get();

    //     dispatch(new ReciveChatMessagejob($total_unread->pluck('id'), 'seen'));

    //     if ($check_room->user_id == $user->id) {
    //         $user2 = User::find($check_room->user_id2);
    //     } else {
    //         $user2 = User::find($check_room->user_id);
    //     }

    //     try {
    //         event(new OpenChat(['chat_room_id' => $check_room->id,'chat_room_type' => $check_room->type,'user2_profile' => $user2->profile ?? null
    //         ], $user2->id));
    //     } catch (\Throwable $th) {
    //         return $th->getMessage();
    //     }

    //     return [
    //         'messages' => ChatMessageResource::collection($data),
    //         'chat_room_id' => $check_room->id
    //     ];

    // }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Call the service method to handle chat room deletion
        $response = $this->chatRoomService->deleteChatRoom($user, $id);

        // Clear cached chat rooms for both users so the list refreshes
        \Cache::forget("chat_rooms_{$user->id}");
        \Cache::forget("chat_rooms_{$id}");

        return response()->json($response);
    }

    public function accept_request(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $response = $this->chatRoomService->acceptRequest($request);

        // Clear cached chat rooms for both users so the list refreshes
        \Cache::forget("chat_rooms_{$request->user()->id}");
        \Cache::forget("chat_rooms_{$request->user_id}");

        return response()->json($response);

    }

    /**
     * POST /api/Chat-room/{id}/read
     * Advance the caller's read cursor for a 1:1 room (monotonic). The 1:1 twin of
     * GroupController::markRead — without it the server recomputes unread from
     * my_last_read_seq=0, so the DM unread badge reappears after reopening. Gated
     * on the caller being a participant of the room.
     */
    public function markRead(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'last_read_seq' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = $request->user()->id;

        $room = ChatRoom::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('user_id2', $userId);
            })
            ->first();

        if (!$room) {
            return Common::apiResponse(false, __('permission denied'), null, 403);
        }

        $seq = (int) ($data['last_read_seq'] ?? 0);

        // When the client omits/zeroes the cursor, compute it server-side from the
        // room's high-water server_seq so legacy clients (and the chat.room auth
        // path) still advance the cursor instead of pinning it at 0.
        if ($seq <= 0) {
            $seq = (int) ChatMessage::where('chat_room_id', $room->id)->max('server_seq');
        }

        // Ensure the member row exists for both legacy (pre-members) and new 1:1
        // rooms before the cursor update — the GREATEST update is a no-op on a
        // missing row, leaving the unread badge stuck.
        ChatRoomMember::firstOrCreate(
            ['chat_room_id' => $room->id, 'user_id' => $userId],
            ['status' => 'active', 'last_read_seq' => 0]
        );

        // Monotonic: never move the cursor backwards (out-of-order client calls,
        // a stale tab) — GREATEST keeps it at the high-water mark. The seq is a
        // bound parameter (not concatenated) so the SET expression is injection-safe.
        DB::update(
            'UPDATE chat_room_members
                SET last_read_seq = GREATEST(CAST(last_read_seq AS SIGNED), ?),
                    updated_at = ?
              WHERE chat_room_id = ? AND user_id = ?',
            [$seq, now(), $room->id, $userId]
        );

        $current = (int) ChatRoomMember::where('chat_room_id', $room->id)
            ->where('user_id', $userId)
            ->value('last_read_seq');

        // Advancing the cursor is the unread-badge fix; the seen RECEIPT is a
        // separate concern. The legacy open path (store/cursor) flips peer
        // messages to 'seen' and broadcasts 'status_update' so the sender's blue
        // tick lights up — but the modern offline-first client reads via this
        // endpoint, not store/cursor. Without this call no seen receipt ever
        // broadcasts on the modern path, so the sender's ticks stay grey forever.
        // markMessagesAsSeen is idempotent (only touches not-yet-seen rows) and
        // never lets a broadcast failure break the read flow.
        $this->chatRoomService->markMessagesAsSeen($room, $request->user());

        return Common::apiResponse(true, __('success'), ['last_read_seq' => max($current, $seq)], 200);
    }

    /**
     * @param int|string|null $userId
     * @param mixed $userIds
     * @param mixed $message
     * @return mixed
     */

    /**
     * @param mixed $userIds
     * @param int|string|null $userId
     * @return void
     */
/*     public function createNewChatRooms(mixed $userIds, int|string|null $userId): void
    {
        $data = [];
        // create chat room and store message
        foreach ($userIds as $userIdDiff) {

            $data[] = [
                'user_id'  => $userId,
                'user_id2' => $userIdDiff,
            ];
        }
        $chunks = array_chunk($data, 1000);
        foreach ($chunks as $chunk) {
            ChatRoom::query()->insert($chunk);
        }
    } */

    /**
     * @param int|string|null $userId
     * @param mixed $userIds
     * @param mixed $message
     * @return void
     */
    /* public function sendMessageToUsers(int|string|null $userId, mixed $userIds, array $message): void
    {
        $timeZone = request()->hasHeader('tz') ? request()->header()['tz'][0] : 'UTC';
        dispatchJobToQueue(new SendMessageToAllUsers($userId, $userIds, $message, timezone: $timeZone), 'heavyProcessing');
        // $userIds = $this->sendMessages($userId, $userIds, $message);

        // $this->createNewChatRooms($userIds, $userId);

        // $this->sendMessages($userId, $userIds, $message);
    } */
}
