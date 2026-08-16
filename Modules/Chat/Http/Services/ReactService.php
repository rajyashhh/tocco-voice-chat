<?php

namespace Modules\Chat\Http\Services;

use App\Models\User;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Events\ReactMessageEvent;
use Modules\Chat\Http\Repositories\ReactRepository;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;

class ReactService
{
    protected $reactRepository;

    public function __construct(ReactRepository $reactRepository)
    {
        $this->reactRepository = $reactRepository;
    }

    public function handleReact($user, $messageId, $reactType)
    {
        $message = ChatMessage::find($messageId);
        if (!$message) {
            return ['status' => 404, 'message' => 'Message not found'];
        }

        $chatRoom = ChatRoom::find($message->chat_room_id);
        if (!$chatRoom) {
            return ['status' => 404, 'message' => 'Chat room not found'];
        }

        $existingReact = $this->reactRepository->findExistingReact($chatRoom->id, $messageId, $user->id);
        $status = null;

        if ($existingReact && $existingReact->react == $reactType) {
            $this->reactRepository->deleteReact($existingReact);
            $status = 'react removed';
        } elseif ($existingReact && $existingReact->react !== $reactType) {
            $this->reactRepository->deleteReact($existingReact);
            $this->reactRepository->createReact([
                'chat_message_id' => $messageId,
                'chat_room_id' => $chatRoom->id,
                'user_id' => $user->id,
                'react' => $reactType,
            ]);
            $status = 'react changed';
        } else {
            $this->reactRepository->createReact([
                'chat_message_id' => $messageId,
                'chat_room_id' => $chatRoom->id,
                'user_id' => $user->id,
                'react' => $reactType,
            ]);
            $status = 'react added';
        }

        $otherUser = $chatRoom->user_id === $user->id
            ? $chatRoom->user_id2
            : $chatRoom->user_id;

        $eventData = [
            'message' => $message,
            'user' => $otherUser,
            'room' => $chatRoom,
        ];

        $messageResource = new ChatMessageResource($message);
        $roomResource = new ChatRoomResource($chatRoom);

        event(new ReactMessageEvent($messageResource->toResponse(request())->getData()->data, $eventData['user'], $roomResource));

        return ['status' => 200, 'react' => $status, 'message' => $messageResource];
    }


}
