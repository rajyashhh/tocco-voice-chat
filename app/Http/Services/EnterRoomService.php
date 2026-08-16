<?php

namespace App\Http\Services;

use App\Helpers\Common;
use App\Models\Banner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnterRoomService
{
    public function checkRoomBan($user, $room)
    {
        if (!$this->isRoomBanned($room->uid, $room->type)) {
            return null;
        }

        if ($room->type === 'audio') {
            return $this->errorResponse(
                __('This room has been closed. Wait until moderators lift the ban.'),
                403,
                ['ban' => true]
            );
        }

        if ($room->type === 'live') {
            if ($user->id == $room->uid) {
                [$duration, $remaining] = Common::banDuration($room->uid, $room->type);
                return $this->errorResponse(
                    __('api.banRoom', ['duration' => $duration, 'remaining' => $remaining]),
                    403,
                    ['ban' => true]
                );
            }

            return $this->errorResponse(
                __('This Live is closed'),
                403,
                ['ban' => true]
            );
        }

        return null;
    }

    private function isRoomBanned(int $ownerId, string $roomType): bool
    {
        return Common::ifRoomHasband($ownerId, $roomType);
    }

    private function errorResponse(string $message, int $code, array $extra = [])
    {
        return Common::apiResponse(0, $message, $extra ?: null, $code);
    }

}
