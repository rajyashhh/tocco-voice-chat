<?php

namespace App\Http\Services;

use App\Exceptions\RoomUserHandling\PermissionNotAllow;
use App\Facades\RoomHelper;
use App\Models\RequestBackgroundImage;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Modules\RoomBoom\Entities\TotalRoomGift;

class RoomService
{
    /**
     * @throws PermissionNotAllow
     * @throws \Exception
     */
    public function muteUserStatus(int $userId, Room $room, bool $isMute = true)
    {
        $admins   = $room->room_admin ?? '';
        $owner_id = $room->uid;

        if (!RoomHelper::checkUserIsAdminOrOwner($admins, $owner_id)) {
            throw new PermissionNotAllow(__('api_responses.you_dont_have_permission'));
        }

        $mutedUsersArr = ($room->muted_users != '') ? explode(',', $room->muted_users) : [];

        if ($isMute) {
            if (in_array($userId, $mutedUsersArr)) throw new \Exception(__('api_responses.user_already_muted'));
            $mutedUsersArr[] = $userId;
        } else {
            if (!in_array($userId, $mutedUsersArr)) throw new \Exception(__('api_responses.user_already_un_muted'));
            $mutedUsersArr = array_diff($mutedUsersArr, [$userId]);
        }
        $room->muted_users = implode(',', $mutedUsersArr);
        $room->save();

    }

    public function getRoomBackground(?Room $room)
    {
        if ($room == null) return '';
        return @$room->final_room_image ?? '';

    }

    public function getOrCreateTotalRoomGift($roomId, $todayStart)
    {
        // No lockForUpdate: locking a created_at RANGE took gap/insert-intention
        // locks and produced deadlocks under concurrent gifts to the same hot room.
        // The actual coin accumulation is done by callers via an atomic
        // ->increment('current_total', ...), which is race-safe on its own; this
        // method only needs to return the row for "today" (creating it if absent).
        $totalRoomGift = TotalRoomGift::where('room_id', $roomId)
            ->where('created_at', '>=', $todayStart)
            ->first();

        if (!$totalRoomGift) {
            $totalRoomGift = TotalRoomGift::create([
                'room_id' => $roomId,
                'current_total' => 0,
            ]);
        }

        return $totalRoomGift;
    }
}
