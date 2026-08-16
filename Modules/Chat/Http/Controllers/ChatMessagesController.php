<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Modules\Chat\Events\Chat;
use Modules\Chat\Events\Conversation;
use App\Http\Controllers\Controller;
use Modules\Chat\Events\OpenChat;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use App\Models\BlockList;
use Modules\Chat\Entities\BlockUser;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Entities\MessageReplay;
use App\Models\User;
use Modules\Chat\Traits\FfmpegTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Common;
use App\Models\BlackList;
use App\Models\Config;
use DB;
use Modules\Chat\Events\CardDeleteMessage;
use Modules\Chat\Events\DeleteMessage;
use Modules\Chat\Http\Services\ChatRoomService;
use Modules\Chat\Http\Services\ChatService;
use Modules\Chat\Http\Services\MessageService;
use Modules\Chat\Http\Requests\ChatStoreRequest;
use Modules\Chat\Http\Requests\DeleteForMeRequest;
use Modules\Chat\Http\Requests\DeleteMessagesRequest;
use Modules\Chat\Http\Requests\UpdateMessageRequest;

class ChatMessagesController extends Controller
{
    use FfmpegTrait;

    public function __construct(public ChatService $chatService, public MessageService $messageService, public ChatRoomService $chatRoomService) {}



    public function store(ChatStoreRequest $request)
    {

        $user = $request->user();

        if ($this->chatService->isUserBlocked($request->user()->id, $request->user_id)) {
            return response()->json([
                'status' => 403,
                'message' => "Unauthorized Block Condition"
            ], 403);
        }

        // Get-or-CREATE the 1:1 room from the two user ids. The offline-first send
        // path carries only the peer user_id (no chat_room_id), and a message to a
        // brand-new peer must materialize the room rather than 404 "Chat not Found"
        // — otherwise the first message of every conversation opened from a profile/
        // picker (which never resolved a server room id) is lost. This is the
        // server-side safety net mirroring getOrCreateChatRoom used by store/cursor;
        // it stays a no-op get for an existing room. Seeding chat_room_members +
        // un-deleting a soft-deleted room are handled inside getOrCreateChatRoom.
        $chatRoom = $this->chatRoomService->getOrCreateChatRoom($user, $request->user_id);

        $total_message = $this->chatService->countMessagesByUserInRoom($chatRoom->id, $user->id);

        $totalDistinctUsers = $this->chatService->countDistinctUsersInRoom($chatRoom->id);

        $maxMessage = \Cache::rememberForever('max_message', function () {
            $setting =   Config::where('name', 'max_message')->first();
            return $setting?->value ?? 3;
        });

        // Resolve the recipient up front so the non-friend message cap can exempt
        // agency owners below.
        if ($chatRoom->user_id != $user->id) {
            $user2 = User::withoutAppends()->find($chatRoom->user_id);
        } else {
            $user2 = User::withoutAppends()->find($chatRoom->user_id2);
        }

        if ($chatRoom->type == 'guest' && $total_message >= $maxMessage && $totalDistinctUsers < 2) {
            // Agency owners can be messaged by anyone at any time (treated like a
            // friend), so the 3-message non-friend cap does not apply to them.
            $recipientIsAgencyOwner = $user2 && $user2->ownAgency()->exists();

            if (!$recipientIsAgencyOwner) {
                return response()->json([
                    'status' => 429,
                    'message' => __("limitChatMessage"),
                ], 429);
            }
        }

        //Files Validations
        if ($request->hasFile('file')) {
            $validExtensions = ['jpeg', 'jpg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'pdf'];
            foreach ($request->file('file') as $file) {
                if (!$this->isValidFileExtension($file, $validExtensions)) {
                    return $this->fileValidationErrorResponse();
                }
            }
        }

        // Persist the Idempotency-Key (client_uuid) at INSERT time so the unique
        // index uq_msg_room_client(chat_room_id, client_uuid) is the gatekeeper:
        // a concurrent retry with the same key collides on insert instead of
        // slipping through and being patched by a later UPDATE. Null for legacy
        // clients that send no header (NULLs stay distinct under the index).
        $clientUuid = trim((string) $request->header('Idempotency-Key', '')) ?: null;

        $messageData = [
            'chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'client_uuid' => $clientUuid,
        ];

        $message = $this->chatService->createChatMessage($messageData);

        // Idempotent retry: a concurrent send with the same Idempotency-Key lost the
        // race on uq_msg_room_client, so createChatMessage recovered the winner's row
        // (wasRecentlyCreated === false). Replay the stored message in the exact shape
        // store() returns WITHOUT re-running side effects — no second file upload,
        // sequence bump, broadcast, or push notification. This is what turns a TOCTOU
        // 500 into a clean idempotent 200.
        if (! $message->wasRecentlyCreated) {
            return [
                'message' => new ChatMessageResource(
                    \Modules\Chat\Entities\ChatMessage::find($message->id)
                ),
                'card' => new ChatRoomResource($chatRoom),
            ];
        }


        // //add status for message
        // if($user2->online == 1 && $user2->current_room_chat == $check_room->id )
        // {
        //     $message->status = 'seen';
        //     $message->update();
        // }
        // else if($user2->online == 1)
        // {
        //     $message->status = 'received';
        //     $message->update();
        // }
        // else{
        //     $tokens_notfacion[] = DB::table('users')->where('id', $user2->id)->value('notification_id');
        //     $title=$user->name;
        //     $body= $message->message ;
        //     Common::send_firebase_notification($tokens_notfacion,$title,$body,messageType: 'message');
        // }

        //insert files to database

        $this->messageService->handleFileUpload($request, $chatRoom, $message, $user);

        $response = $this->messageService->handleMessage($request, $message, $user, $user2, $chatRoom);

        // Clear cached chat rooms for both users so the list reflects the new message
        \Cache::forget("chat_rooms_{$user->id}");
        if ($user2) {
            \Cache::forget("chat_rooms_{$user2->id}");
        }

        //add status for message
        if ($totalDistinctUsers >= 2) {
            $chatRoom->type = 'friend';
        }

        // Broadcasting now happens exactly once, asynchronously, inside the
        // BroadcastChatMessage job dispatched by MessageService::handleMessage().
        // The job re-emits the same Conversation/Chat/OpenChat events with the
        // same payloads and the same `user2 ?? sender` OpenChat fallback, so the
        // inline event() calls that used to live here would double-broadcast every
        // 1:1 message on the default Pusher path. They were removed; the job is the
        // single broadcaster and rides broadcasting.default (pusher|dual|centrifugo).

        // Send a push only when the recipient is NOT currently inside this chat.
        // The old `!$user2->current_room_chat != $chatRoom->id` was a malformed
        // double-negation (cast-to-bool then compared to an int), so the guard
        // never matched correctly.
        if ($user2 && $user2->current_room_chat != $chatRoom->id) {
            $this->messageService->sendNotification($user2, $message);
        }

        return [
            'message' =>    $response['message_resource'],
            'card' =>  new ChatRoomResource($chatRoom)
        ];
    }

    private function isValidFileExtension($file, $validExtensions)
    {
        $extension = $file->getClientOriginalExtension();

        return in_array($extension, $validExtensions);
    }

    private function fileValidationErrorResponse()
    {
        return response()->json([
            'status' => 404,
            'message' => "File doesn't match our records",
        ], 404);
    }

    public function update(UpdateMessageRequest $request)
    {

        $user = $request->user();
        $response = $this->chatService->updateMessage(
            $request->message_id,
            $request->message,
            $user
        );

        if (isset($response['status']) && $response['status'] === 404) {
            return response()->json($response, 404);
        }

        // Clear cached chat rooms for both users
        $msg = \Modules\Chat\Entities\ChatMessage::find($request->message_id);
        if ($msg) {
            $chatRoom = \Modules\Chat\Entities\ChatRoom::find($msg->chat_room_id);
            if ($chatRoom) {
                \Cache::forget("chat_rooms_{$chatRoom->user_id}");
                \Cache::forget("chat_rooms_{$chatRoom->user_id2}");
            }
        }

        return new ChatMessageResource($response);
    }

    public function deleteForAll(DeleteMessagesRequest $request)
    {

        $response = $this->chatService->deleteMessages($request->id, $request->user());

        if ($response['status'] !== 200) {
            return response()->json($response, $response['status']);
        }

        // Clear cached chat rooms for both users
        $firstMsg = \Modules\Chat\Entities\ChatMessage::find($request->id[0]);
        if ($firstMsg) {
            $chatRoom = \Modules\Chat\Entities\ChatRoom::find($firstMsg->chat_room_id);
            if ($chatRoom) {
                \Cache::forget("chat_rooms_{$chatRoom->user_id}");
                \Cache::forget("chat_rooms_{$chatRoom->user_id2}");
            }
        }

        return response()->json($response);
    }

    public function deleteForMe(DeleteForMeRequest $request)
    {

        $response = $this->chatService->deleteForUser($request->id, $request->user());

        if ($response['status'] !== 200) {
            return response()->json($response, $response['status']);
        }

        // Clear cached chat rooms for the current user
        \Cache::forget("chat_rooms_{$request->user()->id}");

        return response()->json($response);
    }
}
