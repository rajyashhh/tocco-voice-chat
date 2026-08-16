<?php
namespace Modules\Chat\Http\Repositories;

use Modules\Chat\Entities\PinToTop;

class PinToTopRepository
{
    public function pinChatToTop(int $chatRoomId, int $userId): PinToTop
    {
        $pinToTop = PinToTop::create([
            'chat_room_id' => $chatRoomId,
            'user_id' => $userId
        ]);

        return $pinToTop;
    }

    public function removePin(int $userId, string $chatRoomId)
    {
        PinToTop::where('chat_room_id', $chatRoomId)->where('user_id', $userId)->delete();
    }
}
