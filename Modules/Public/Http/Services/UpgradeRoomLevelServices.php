<?php

namespace Modules\Public\Http\Services;

use Modules\Public\Jobs\RewardWinnerLevel;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use App\Models\Room;
use Modules\Public\Entities\WinnerLevelInterval;


class UpgradeRoomLevelServices
{
    public function sendGift(Room &$room, $diamonds)
    {
        $this->addDiamond($room, (int)$diamonds);
    }

    public function  addDiamond(Room &$room, $diamonds)
    {
        $room->total_diamond += $diamonds;
        $room->save();
        $this->checkUserLevelUpgrated($room);
    }


    public function checkUserLevelUpgrated(Room &$room)
    {
        $user = $room->owner;
        $oldroomLevel = $room->level;

        $roomLevel = (new UpdateUserWhenSendGift())->getRoomLevelDetails($room->total_diamond);
        if ($roomLevel && $roomLevel->level > $oldroomLevel) {
            $room->level_id = $roomLevel->id;
            $room->level =  $roomLevel->level;
            $room->save();
            // $room->exp =  $roomLevel->exp;
            $hadNotRewards = $this->hadNotRewards($user->id, $roomLevel->level);
            if ($hadNotRewards) {
                dispatch(new RewardWinnerLevel($user->id, $roomLevel->level, 3))->onQueue('level_rewards');
            }
        }
    }

    private function hadNotRewards(int $userId, int $roomLevel)
    {
        return !WinnerLevelInterval::query()->whereHas('levelInterval', function ($query) {
            $query->where('type', 3);
        })->where('min', '<=', $roomLevel)->where('max', '>=', $roomLevel)->where('user_id', $userId)->exists();
    }
}
