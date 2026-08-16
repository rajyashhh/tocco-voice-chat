<?php

namespace App\Http\Services;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Models\Room;
use App\Models\User;
use App\Models\GiftLog;
use App\Models\AppFeature;
use App\Models\RoomGiftTarget;
use App\Facades\CustomNotification;
use App\Models\RoomOwnerAchievement;

class RoomAchievementTargetService
{


    public function sumGiftPrice($roomId)
    {
          return GiftLog::where('room_id', $roomId)->where('room_gift_status', true)->sum('giftPrice');
    }

    public function reachTarget($roomId, $targetId)
    {
        return !RoomOwnerAchievement::where('target_id', $targetId)->where('room_id', $roomId)->exists();
    }

    public function roomTarget(Room $room)
    {
        $appFeature = AppFeature::where('slug', 'room_gift_target')->first();
        if ($appFeature && $appFeature?->status == 1) {
            $totalRoomPrice = $this->sumGiftPrice($room->id);
            $roomTarget = RoomGiftTarget::where('target', '<=', $totalRoomPrice)->orderByDesc('target')->first();

            if (!$roomTarget) return;
            if ($this->reachTarget($room->id, $roomTarget->id)) {
                $user = User::find($room->uid);
                if (!$user) return;
              
                $amountBefore =  $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $roomTarget->coins,
                    $amountBefore,
                    UserCoinLogType::ROOM_TARGET,
                );
               
                $user->di += $roomTarget->coins;
                $user->save();
                RoomOwnerAchievement::create([
                    'target_id' => $roomTarget->id,
                    'room_id' => $room->id,
                    'coins' => $roomTarget->coins,
                ]);
                CustomNotification::roomAchievementTarget($user, $roomTarget->coins, $room->room_name);
            }
        }
    }
}
