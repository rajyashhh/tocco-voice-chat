<?php

namespace Modules\Chat\Http\Services;

use Modules\Chat\Http\Repositories\ChatRepository;
use Modules\Chat\Http\Repositories\PinToTopRepository;

class PinToTopService
{


    public function __construct(public ChatRepository $chatRoomRepo, public PinToTopRepository $pinToTopRepo) {}

    public function store(int $userId, int $otherUserId): array
    {
        // Find the chat room between the current user and the other user
        $checkRoom = $this->chatRoomRepo->findChatRoomBetweenUsers($userId, $otherUserId);

        // If the chat room doesn't exist, return an error response
        if (!$checkRoom) {
            return [
                'status' => 404,
                'message' => 'Chat not Found',
            ];
        }

        // Pin the chat to the top
        $this->pinToTopRepo->pinChatToTop($checkRoom->id, $userId);

        return [
            'status' => 200,
            'message' => 'Chat added to top',
        ];
    }

    public function removePinFromTop(int $userId, string $chatRoomId): array
    {
        // Check if the chat room exists for the user
        $checkRoom = $this->chatRoomRepo->findChatRoomForUser($userId, $chatRoomId);

        if (!$checkRoom) {
            return [
                'status' => 404,
                'message' => 'Chat not Found',
            ];
        }

        // Remove the pin from the top
        $this->pinToTopRepo->removePin($userId, $chatRoomId);

        return [
            'status' => 200,
            'message' => 'Chat Removed from Top',
        ];
    }
}
