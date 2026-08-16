<?php

namespace Modules\Chat\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Log;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Events\Chat;
use Modules\Chat\Events\Chatupdate;
use Modules\Chat\Events\Conversation;
use Modules\Chat\Events\ConversationUpdate;
use Modules\Chat\Events\UpdateConversationUser;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\Chat\Traits\CreatedAtConvert;

class SendMessageToAllUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use CreatedAtConvert;

    /**
     * Create a new job instance.
     */
    public function __construct(private int|string|null $userId,private mixed $userIds,private array $message, private string $timezone = 'UTC')
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendMessageToUsers($this->userId, $this->userIds, $this->message, timezone: $this->timezone);
    }

    /**
     * @param int|string|null $userId
     * @param mixed $userIds
     * @param mixed $message
     * @return void
     */
    public function sendMessageToUsers(int|string|null $userId, mixed $userIds, array $message, $timezone = 'UTC'): void
    {
        $userIds = $this->sendMessages($userId, $userIds, $message, timezone: $timezone);

        $this->createNewChatRooms($userIds, $userId);

        $this->sendMessages($userId, $userIds, $message, timezone: $timezone);
    }

    /**
     * @param mixed $userIds
     * @param int|string|null $userId
     * @return void
     */
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
        $chunks = array_chunk($data, 1000 );
        foreach ($chunks as $chunk) {
            ChatRoom::query()->insert($chunk);
        }
    }


    /**
     * @param int|string|null $userId
     * @param mixed $userIds
     * @param mixed $message
     * @return mixed
     */
    public function sendMessages(int|string|null $userId, mixed $userIds, array $data, $timezone = 'UTC'): mixed
    {
        $message = @$data['message'];
        $url = @$data['url'];
        $user=User::find($userId);

        ChatRoom::select(['id', 'user_id', 'user_id2'])
            ->withCount(['unReadMessages' => fn($q) => $q->where('user_id', $userId)])
                ->with([
                    "userOne" => fn($q) => $q->where("online",1)->where('id', '!=', $userId)->select(['id', 'notification_id', 'online', 'lan' ]),
                    "userTwo" => fn($q) => $q->where("online",1)->where('id', '!=', $userId)->select(['id', 'notification_id', 'online', 'lan'])
                    ])

                ->where(fn( $q) => $q->where('user_id', $userId)->whereIn('user_id2', $userIds))
                ->orWhere(fn( $q) => $q->where('user_id2', $userId)->whereIn('user_id', $userIds))
                ->chunk(400, function ($chatRooms) use (&$userIds, $userId, $message, $url, $timezone, $user) {
                    $ids  = $chatRooms->pluck('user_id')->toArray();
                    $ids2 = $chatRooms->pluck('user_id2')->toArray();

                    $allIds = array_unique(array_merge($ids, $ids2));

                    $userIds = array_diff($userIds, $allIds);

                    $data = [];
                    foreach ($chatRooms as $chatRoom) {
                       // $user2ChatId = $chatRoom->user_id != $userId ? $chatRoom->user_id : $chatRoom->user_id2;
                       $user2ChatId = $chatRoom->user_id != $userId ?  $chatRoom->user_id : $chatRoom->user_id2 ;
                        $user2 = $chatRoom->user_id != $userId ?  $chatRoom->userOne : $chatRoom->userTwo ;
                        $userChatId = $userId;
                        $result = [
                            'chat_room_id' => $chatRoom->id,
                            'user_id'      => $userChatId,
                            'message'      => $message,
                            'status'       => 'received',
                            'type'         => $url ? 'img' : 'text',
                            'file'         => $url,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];

                        $data[]     = $result;


                        if ($user2) {
                           $messageData =
                                [
                                    'replay' => null,
                                    'id' => 10000,
                                    'user_id' => $userId,
                                    'message' => $message,
                                    'status' => 'received',
                                    'type' => $url ? 'img' : 'text',
                                    'chat_room_id' => $chatRoom->id,
                                    'server_room_id' => $chatRoom->id,
                                    'sender_deleted' => false,
                                    'receiver_deleted' => false,
                                    'reacts' => null,
                                    'albums' => $url ? [
                                        'user_id' => $userId,
                                        'file'    => $url,
                                        'frame'    =>  null,
                                        'type'    => $url ? 'img' : 'text',
                                    ] : null,
                                    'created_at' => (string)$this->create_at($timezone, now()),
                                ];
                            $result2 = [
                                'chat_room_id' => $chatRoom->id,
                                'chat_id' => $chatRoom->id,
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'img' => @$user->profile->avatar,
                                'message' => $message,
                                'status' => 'received',
                                'unread_message' => $chatRoom->un_read_messages_count + 1 ?? 1,
                                'type' => $url ? 'img' : 'text',
                                'file' => $url,
                                'last_message' => $messageData,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            event(new ConversationUpdate($messageData, $chatRoom->id));
                            event(new Chatupdate($result2, $user2ChatId));
                        }
                    }


                    DB::table('chat_messages')->insert($data);

                    if ($url != null){
                        $lastId = DB::getPdo()->lastInsertId('chat_messages');

                        $data = [];
                        foreach ($chatRooms as $chatRoom) {
                            $userChatId = $chatRoom->user_id != $userId ? $chatRoom->user_id : $chatRoom->user_id2;
                            $data[]     = [
                                'chat_room_id' => $chatRoom->id,
                                'user_id'      => $userChatId,
                                'chat_message_id'      => $lastId,
                                'type'         => 'img',
                                'file'         => $url,
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ];
                            $lastId++;
                        }
                        MessageAlbum::insert($data);
                    }



                });
        return $userIds;
    }

}
