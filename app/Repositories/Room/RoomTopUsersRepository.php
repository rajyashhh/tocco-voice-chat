<?php

namespace App\Repositories\Room;

use App\Models\RoomTopUser;

class RoomTopUsersRepository
{

    public function findOrCreate($roomId, $userId)
    {
        return RoomTopUser::query()->firstOrCreate(['room_id' => $roomId, 'user_id' => $userId]);
    }

    public function getRoomTopUser($roomId, $with = [])
    {
        return RoomTopUser::query()->whereHas('user')
                          ->with($with)
                          ->where('room_id', $roomId)
                          ->orderByDesc('coins')
                          ->first();
    }

}
