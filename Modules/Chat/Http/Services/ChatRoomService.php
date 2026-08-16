<?php

namespace Modules\Chat\Http\Services;

use App\Models\GiftLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Entities\React;
use Modules\Chat\Events\OpenChat;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\Chat\Jobs\SendMessageToAllUsers;
use Illuminate\Database\Eloquent\Builder;
use Modules\Reals\Entities\Real;

class ChatRoomService
{
    public function __construct(protected NextServerSeqService $seq)
    {
    }

    /**
     * Handle the invitation logic.
     *
     * @param array  $data
     * @param int    $userId
     * @param string $type
     * @param array  $userIds
     * @param array  $exceptIds
     * @return void
     */
    public function handleInvite(array $data, int $userId, string $type, array $userIds = [], array $exceptIds = [])
    {
        if ($type === 'all') {
            $this->inviteToAll($userId, $data, $exceptIds);
        } else {
            $this->inviteToSpecificUsers($userId, $data, $userIds);
        }

        $this->countReel($data, $type, $userId, $userIds);
    }

    public function countReel($data,  $type, $userId, $userIds)
    {
        $parts = explode(':', str_replace("\n", ':', $data['message']));
        $reelId = $parts[4] ?? null;
        $reel = Real::find($reelId);
        if (!$reel) return true;
        $user = User::find($userId);
        if ($type == 'all' && $userIds == null) {
            $reel->share_num += $user->number_of_friends;
        } elseif ($type == 'not' && $userIds != null) {
            $reel->share_num += ($user->number_of_friends - count($userIds));
        } elseif ($type == 'one') {
            $countUsers = count($userIds);
            $reel->share_num += $countUsers;
        }
        $reel->save();
        return true;
    }

    /**
     * Invite all followers and followeds except specified IDs.
     *
     * @param int   $userId
     * @param array $data
     * @param array $exceptIds
     * @return void
     */
    private function inviteToAll(int $userId, array $data, array $exceptIds)
    {
        User::query()
            ->select('id')
            ->whereHas('followers', fn($q) => $q->where('user_id', $userId))
            ->whereHas('followeds', fn($q) => $q->where('followed_user_id', $userId))
            ->whereNotIn('id', $exceptIds)
            ->chunk(400, function ($users) use ($userId, $data) {
                $userIds = $users->pluck('id')->toArray();
                $this->sendMessageToUsers($userId, $userIds, $data);
            });
    }

    /**
     * Invite specific users.
     *
     * @param int   $userId
     * @param array $data
     * @param array $userIds
     * @return void
     */
    private function inviteToSpecificUsers(int $userId, array $data, array $userIds)
    {
        if (!empty($userIds)) {
            $this->sendMessageToUsers($userId, $userIds, $data);
        }
    }

    public function sendMessageToUsers(int|string|null $userId, mixed $userIds, array $message): void
    {
        $timeZone = request()->hasHeader('tz') ? request()->header()['tz'][0] : 'UTC';
        dispatchJobToQueue(new SendMessageToAllUsers($userId, $userIds, $message, timezone: $timeZone), 'heavyProcessing');
        // $userIds = $this->sendMessages($userId, $userIds, $message);

        // $this->createNewChatRooms($userIds, $userId);

        // $this->sendMessages($userId, $userIds, $message);
    }

    public function findUsersByName(string $name)
    {
        return User::where('name', 'LIKE', '%' . $name . '%')
            ->select('id', 'name')
            ->get();
    }

    public function getChatRooms($user, $uuid)
    {
        $user = User::with('chats')->find($user->id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'user not found',
                'status' => 200,
            ];
        }

        // Get top chats
        $topChats = $user->chats->pluck('id')->toArray();

        // Get user chats (friends)
        $friends = ChatRoom::withCount([
            'messages as distinct_users_count' => function ($query) {
                $query->select(DB::raw("COUNT(DISTINCT user_id)"));
            }
        ])->WhereHas('messages')
            ->select(
                'chat_rooms.*',
                DB::raw('(SELECT MAX(created_at) FROM chat_messages WHERE chat_messages.chat_room_id = chat_rooms.id) AS last_message_created_at'),
                DB::raw('(SELECT COUNT(DISTINCT user_id) FROM chat_messages WHERE chat_messages.chat_room_id = chat_rooms.id) AS distinct_users_count')
            )
            ->where(function ($q) use ($topChats, $user) {
                $q->where(function ($query) use ($topChats, $user) {
                    $query->whereNotIn('chat_rooms.id', $topChats)
                        ->where('chat_rooms.user_id', $user->id)
                        ->where(function ($sub) {
                            $sub->where('chat_rooms.type', 'friends')
                                ->orWhere(function ($sq) {
                                    $sq->where('chat_rooms.type', 'guest');
                                });
                        });
                })
                    ->orWhere(function ($query) use ($topChats, $user) {
                        $query->whereNotIn('chat_rooms.id', $topChats)
                            ->where('chat_rooms.user_id2', $user->id)
                            ->where(function ($sub) {
                                $sub->where('chat_rooms.type', 'friends')
                                    ->orWhere(function ($sq) {
                                        $sq->where('chat_rooms.type', 'guest');
                                    });
                            });
                    });
            })->havingRaw("(
                (chat_rooms.type = 'friends') 
                OR (chat_rooms.type = 'guest' AND distinct_users_count >= 2)
                OR (chat_rooms.type = 'guest' AND chat_rooms.user_id = {$user->id})
            )")
            ->when($uuid, function ($q) use ($uuid) {
                $q->where(function ($q) use ($uuid) {
                    $q->whereHas('userOne', function ($qq) use ($uuid) {
                        $qq->where('uuid', 'like', "%$uuid%");
                    })
                        ->orWhereHas('userTwo', function ($qq2) use ($uuid) {
                            $qq2->where('uuid', 'like', "%$uuid%");
                        });
                });
            })
            ->where(function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->whereNull('user_1_deleted');
                })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('user_id2', $user->id)
                            ->whereNull('user_2_deleted');
                    });
            })
            ->groupBy([
                'chat_rooms.id',
                'chat_rooms.user_id',
                'chat_rooms.user_id2',
                'chat_rooms.type',
                'user_1_deleted',
                'user_2_deleted',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('last_message_created_at')
            ->paginate(request('per_page', 50));

        // // Get chat requests (guest)
        // $guestChats = ChatRoom::WhereHas('messages')
        //     ->select('chat_rooms.*')
        //     ->where('chat_rooms.user_id2', $user->id)
        //     ->where('chat_rooms.type', 'guest')
        //     ->with('messages')
        //     ->join('chat_messages', 'chat_rooms.id', '=', 'chat_messages.chat_room_id')
        //     ->orderBy('chat_messages.id', 'desc')
        //     ->paginate(20);

        // Get unread messages
        $chatRoomIds = ChatRoom::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)->orWhere('user_id2', $user->id);
        })->where('type', 'friends')->pluck('id')->toArray();

        $unreadMessages = ChatMessage::whereIn('chat_room_id', $chatRoomIds)
            ->where('user_id', '!=', $user->id)
            ->where('status', '!=', 'seen')
            ->paginate(20);

        return [
            'success' => true,
            'message' => 'successfully',
            'data' => [
                'top_chats' => ChatRoomResource::collection($user->chats),
                'chat' => ChatRoomResource::collection($friends),
                // 'request_chat' => ChatRoomResource::collection($guestChats),
                'total_unread_messages' => $unreadMessages->count(),
                'unread_messages' => ChatMessageResource::collection($unreadMessages),
            ],
            'status' => 200,
        ];
    }

    public function getGUestChatRooms($user)
    {
        $user = User::with('chats')->find($user->id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'user not found',
                'status' => 200,
            ];
        }

        // Get chat requests (guest) - only for the receiver (user_id2)
        $guestChats = ChatRoom::WhereHas('messages')
            ->select('chat_rooms.*')
            ->where('chat_rooms.user_id2', $user->id)  // المستقبل بس يشوف طلبات المراسلة
            ->where('chat_rooms.type', 'guest')
            ->has('messages')
            ->withCount([
                'messages as distinct_users_count' => function ($query) {
                    $query->select(DB::raw("COUNT(DISTINCT user_id)"));
                }
            ])
            ->having('distinct_users_count', '<', 2)
            // ->join('chat_messages', 'chat_rooms.id', '=', 'chat_messages.chat_room_id')
            // ->orderBy('chat_messages.id', 'desc')
            ->paginate(20);

        // Get unread messages
        $chatRoomIds = ChatRoom::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)->orWhere('user_id2', $user->id);
        })->where('type', 'guest')->pluck('id')->toArray();

        $unreadMessages = ChatMessage::whereIn('chat_room_id', $chatRoomIds)
            ->where('user_id', '!=', $user->id)
            ->where('status', '!=', 'seen')
            ->paginate(20);

        return [
            'success' => true,
            'message' => 'successfully',
            'data' => [
                'request_chat' => ChatRoomResource::collection($guestChats),
                'total_unread_messages' => $unreadMessages->count(),
                'unread_messages' => ChatMessageResource::collection($unreadMessages),
            ],
            'status' => 200,
        ];
    }


    public function getOrCreateChatRoom($user, $userId2)
    {
        // Find existing chat room or create a new one
        $chatRoom = ChatRoom::where(function ($query) use ($user, $userId2) {
            $query->where(function ($q) use ($user, $userId2) {
                $q->where('user_id', $user->id)
                    ->where('user_id2', $userId2);
            })
                ->orWhere(function ($q) use ($user, $userId2) {
                    $q->where('user_id', $userId2)
                        ->where('user_id2', $user->id);
                });
        })->first();

        if (!$chatRoom) {
            $user2 = User::find($userId2);
            $type = 'guest';
            if ($user->followBack($user2)) {
                $type = 'friends';
            }

            $chatRoom = ChatRoom::create([
                'user_id' => $user->id,
                'user_id2' => $userId2,
                'type' => $type,
            ]);

            // Seed both members so the read-cursor (last_read_seq) and the seen
            // receipt path work for new 1:1 rooms exactly like groups. firstOrCreate
            // keeps it idempotent if a row already exists.
            foreach ([$user->id, $userId2] as $memberId) {
                ChatRoomMember::firstOrCreate(
                    ['chat_room_id' => $chatRoom->id, 'user_id' => $memberId],
                    ['status' => 'active', 'last_read_seq' => 0]
                );
            }
        }

        if ($chatRoom) {
            if ($chatRoom->user_1_deleted) {
                $chatRoom->update(['user_1_deleted' => null]);
            }

            if ($chatRoom->user_2_deleted) {
                $chatRoom->update(['user_2_deleted' => null]);
            }
        }

        return $chatRoom;
    }

    public function getCreateChatRoomId($id)
    {
        $chatRoom = ChatRoom::where('id', $id)->first();

        if (!$chatRoom) {
            return false;
        }

        if ($chatRoom) {
            if ($chatRoom->user_1_deleted) {
                $chatRoom->update(['user_1_deleted' => null]);
            }

            if ($chatRoom->user_2_deleted) {
                $chatRoom->update(['user_2_deleted' => null]);
            }
        }

        return $chatRoom;
    }

    public function getChatMessages($chatRoomId, $request, $user)
    {
        // Get messages with reacts and albums for the chat room
        // $query = ChatMessage::where('chat_room_id', $chatRoomId)
        //     ->with('reacts', 'albums')
        //     ->orderBy('id', 'desc');

        $query = ChatMessage::where('chat_room_id', $chatRoomId)
            ->with('reacts', 'albums')
            ->orderBy('id', 'desc')
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    // If current user is sender (user_1)
                    $sub->where('user_id', $user->id)
                        ->where(function ($inner) {
                            $inner->whereNull('user_1_deleted');
                        });
                })
                    ->orWhere(function ($sub) use ($user) {
                        // If current user is receiver (user_2)
                        $sub->where('user_id', '!=', $user->id)
                            ->where(function ($inner) {
                                $inner->whereNull('user_2_deleted');
                            });
                    })
                    ->orWhere(function ($sub) use ($user) {
                        // If current user is receiver (user_2)
                        $sub->whereNotNull('user_1_deleted')->whereNotNull('user_2_deleted');
                    });
            });

        if ($request && $request->type && $request->message_id) {
            if ($request->type == 'new') {
                return $query->where('id', '>', $request->message_id)->get();
            } elseif ($request->type == 'old') {
                return $query->where('id', '<', $request->message_id)->paginate(request('per_page', 10));
            }
        }

        return $query->paginate(request('per_page', 10));
    }

    public function markMessagesAsSeen($checkRoom, $user)
    {
        // The peers whose messages are about to be marked seen, and the high-water
        // server_seq up to which they were read — captured BEFORE the update so the
        // receipt carries the exact range the reader consumed.
        $unread = ChatMessage::where('chat_room_id', $checkRoom->id)
            ->where('user_id', '!=', $user->id)
            ->where('status', '!=', 'seen')
            ->get(['user_id', 'server_seq']);

        // Mark unread messages as seen
        ChatMessage::where('chat_room_id', $checkRoom->id)
            ->where('user_id', '!=', $user->id)
            ->where('status', '!=', 'seen')
            ->update(['status' => 'seen']);

        if ($unread->isEmpty()) {
            return;
        }

        // Unified seen-receipt contract: notify each sender on their own personal
        // channel (user-{senderId} -> user:#{senderId}) so the client can flip its
        // own messages with serverSeq <= up_to_seq to 'seen'. Never let a broadcast
        // failure break the read flow — the REST status is the source of truth.
        $upToSeq = (int) $unread->max('server_seq');

        try {
            $broadcaster = Broadcast::connection();
            foreach ($unread->pluck('user_id')->unique() as $senderId) {
                $broadcaster->broadcast(
                    ['user-' . $senderId],
                    'status_update',
                    [
                        'type'         => 'status_update',
                        'chat_room_id' => (int) $checkRoom->id,
                        'status'       => 'seen',
                        'up_to_seq'    => $upToSeq,
                        'reader_id'    => (int) $user->id,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('markMessagesAsSeen broadcast failed', [
                'chat_room_id' => $checkRoom->id,
                'reader_id'    => $user->id,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    public function getUserInChatRoom($checkRoom, $user)
    {
        // Get the second user in the chat room
        return $checkRoom->user_id == $user->id
            ? User::withTrashed()->find($checkRoom->user_id2)
            : User::withTrashed()->find($checkRoom->user_id);
    }

    public function handleChatOpenEvent($checkRoom, $user, $user2)
    {
        // Dispatch the event to open the chat room
        try {
            $roomResource = new ChatRoomResource($checkRoom);
            event(new OpenChat($roomResource->toResponse(request())->getData()->data, $user2 ?? $user, $checkRoom));
        } catch (\Throwable $th) {

            throw $th;
        }
    }

    public function getRoomData($user2)
    {
        $room = Room::where('id', $user2?->now_room_uid)->first();
        // $room = $user2?->nowRoom;
        $isHideRoom = $room?->owner?->getPackWithType(16);
        $room = !$isHideRoom ? $room : null;
        return [
            'room_owner_id' => $user2?->now_room_uid,
            'owner' => [
                'uuid' => $user2->uuid ?? 0,
                'deleted_at' => $user2?->deleted_at,
            ],
            'has_password' => $room && $room->room_pass ? true : false,
            'room' => [
                'id' => @$room->id ?? 0,
                'name'  => @$room->room_name ?? '',
                'image' =>  @$room->room_cover ?? '',
                'mode' => @$room->mode ?? 0,
                'room_background' => @$room->final_room_image ?? '',
                'exp' => @$room?->session_string,
                "is_live" => @$room->is_live ?: false,
                'stream_type'         =>  @$room->type ?? 'audio',
            ],


        ];
    }

    public function prepareResponseData($messages, $checkRoom, $user2, $roomData)
    {
        // Prepare the data for the API response
        return [
            'messages' => ChatMessageResource::collection($messages),
            'chat_room_id' => $checkRoom->id,
            'user_now_room' => $roomData
        ];
    }


    public function deleteChatRoom($user, $userId2)
    {


        $checkRoom = ChatRoom::where(function ($query) use ($user, $userId2) {
            $query->where(function ($q) use ($user, $userId2) {
                $q->where('user_id', $user->id)
                    ->where('user_id2', $userId2);
            })->orWhere(function ($q) use ($user, $userId2) {
                $q->where('user_id', $userId2)
                    ->where('user_id2', $user->id);
            });
        })->first();

        if (!$checkRoom) {
            return [
                'status' => 404,
                'message' => 'Chat not Found'
            ];
        }

        $midea = MessageAlbum::where('chat_room_id', $checkRoom->id)->get();
        $mideaStrings = $midea->flatMap(function ($item) {
            return [$item->file, $item->frame];
        })->toArray();


        if ($checkRoom->user_id == $user->id){
            $checkRoom->update(['user_1_deleted' => now()]);
        } else {
            $checkRoom->update(['user_2_deleted' => now()]);
        }


        if ($checkRoom->user_1_deleted && $checkRoom->user_2_deleted){
            try {
                Storage::disk('gcs')->deleteDirectory('Chat_' . env('APP_ENV') . '/chat_' . $checkRoom->id);
            } catch (\Throwable $th) {
                Log::error('Error deleting chat room storage: ' . $th->getMessage());
            }

            MessageAlbum::where('chat_room_id', $checkRoom->id)->delete();
            ChatMessage::where('chat_room_id', $checkRoom->id)->delete();
            React::where('chat_room_id', $checkRoom->id)->delete();

            $checkRoom->delete();
        } else {
            ChatMessage::where('chat_room_id', $checkRoom->id)
                ->chunk(200, function ($messages) use ($user) {
                    foreach ($messages as $msg) {
                        if ($msg->user_id == $user->id) {
                            $msg->user_1_deleted = now();
                        } else {
                            $msg->user_2_deleted = now();
                        }

                        if ($msg->user_1_deleted && $msg->user_2_deleted) {
                            $msg->delete();
                        } else {
                            $msg->save();
                        }
                    }
                });
        }

        return [
            'status' => 200,
            'message' => 'Chat Deleted',
            'midea' => $mideaStrings
        ];
    }


    public function acceptRequest($request)
    {

        $user = $request->user();
        // Check if the chat room exists
        $checkRoom = ChatRoom::where('user_id', $request->user_id)
            ->where('user_id2', $user->id)
            ->where('type', 'guest')
            ->first();

        if (!$checkRoom) {
            return [
                'status' => 404,
                'message' => 'Chat not Found',
            ];
        }

        // Update the chat room type to 'friends'
        $checkRoom->type = 'friends';
        $checkRoom->update();

        // Get the messages related to the chat room
        $data = ChatMessage::where('chat_room_id', $checkRoom->id)
            ->with('reacts', 'albums')
            ->get();

        // Return the formatted message data
        return ChatMessageResource::collection($data);
    }

    public function sendMessages(int|string|null $userId, mixed $userIds, array $data): mixed
    {
        $message = @$data['message'];
        $url = @$data['url'];

        ChatRoom::query()
            ->select(['id', 'user_id', 'user_id2'])
            ->where(fn(Builder $q) => $q->where('user_id', $userId)->whereIn('user_id2', $userIds))
            ->orWhere(fn(Builder $q) => $q->where('user_id2', $userId)->whereIn('user_id', $userIds))
            ->chunk(400, function ($chatRooms) use (&$userIds, $userId, $message, $url) {
                $ids  = $chatRooms->pluck('user_id')->toArray();
                $ids2 = $chatRooms->pluck('user_id2')->toArray();

                $allIds = array_unique(array_merge($ids, $ids2));

                $userIds = array_diff($userIds, $allIds);

                $now = now();

                // Each room receives exactly one message in this path. Allocate the
                // room's next server_seq (reserve a 1-wide block per room, in row
                // order) and refresh its last_message_at pointer, then bulk-insert
                // the whole chunk in a single short transaction so the seq values
                // and the inserted rows never diverge under concurrency.
                DB::transaction(function () use ($chatRooms, $userId, $message, $url, $now) {
                    $data = [];
                    foreach ($chatRooms as $chatRoom) {
                        $userChatId = $chatRoom->user_id != $userId ? $chatRoom->user_id : $chatRoom->user_id2;
                        $serverSeq  = $this->seq->reserve($chatRoom->id, 1)['from'];

                        $data[] = [
                            'chat_room_id' => $chatRoom->id,
                            'user_id'      => $userChatId,
                            'client_uuid'  => null,
                            'server_seq'   => $serverSeq,
                            'message'      => $message,
                            'status'       => 'received',
                            'type'         => 'img',
                            'file'         => $url,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];

                        ChatRoom::query()
                            ->where('id', $chatRoom->id)
                            ->update(['last_message_at' => $now]);
                    }

                    ChatMessage::insert($data);
                });
            });
        return $userIds;
    }


    public function createNewChatRooms(mixed $userIds, int|string|null $userId): void
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
    }
}
