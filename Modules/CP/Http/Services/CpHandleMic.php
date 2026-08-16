<?php

namespace Modules\CP\Http\Services;

use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\Cp;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use App\Models\Ware;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Enums\TargetType;
use Modules\CP\Entities\CpLevelGift;
use Modules\CP\Entities\CpLevelTakeGift;
use Modules\CP\Entities\CpRoomHistory;
use Modules\CP\Enums\CpStatus;
use Modules\CP\Repositories\CpRepository;
use Modules\CP\Traits\CpHandlingTrait;

class CpHandleMic
{
    use CpHandlingTrait;
    protected $cpRepository;

    public function __construct(CpRepository $cpRepository)
    {
        $this->cpRepository = $cpRepository;
    }

    public function handleCpLovely($user, $room, $position)
    {
        $existingCps = Cp::where(function ($query) use ($user) {
            $query->where("user_one_id", $user->id)
                  ->orWhere("user_two_id", $user->id);
        })->whereIn("status", [
            CpStatus::ACTIVE->value,
            CpStatus::RESTORED->value
        ])->get();

        if ($existingCps->isEmpty()) {
            return false;
        }

        if ($room->mode == 0 && $position == 0) {
            return true;
        }

        if ($room->mode == 0){
            $userSeats = $this->getUserNearby($position - 1, mode: $room->mode);
            $userSeats = array_map(fn($item) => $item + 1 , $userSeats);
        }else{

            $userSeats = $this->getUserNearby($position, mode: $room->mode);
        }

        foreach ($userSeats as $antherUserPosition) {
            $newMic = explode(',', $room->microphone);
            $userOtherId = $newMic[$antherUserPosition];

            $existingCp = $this->cpRepository->checkExistingCpLovly($user->id, $userOtherId);
            if ($existingCp) {
                $this->handleCpRoomHistory($user, $room, $position, $antherUserPosition, $userOtherId);
//                $this->sendCpLovelyMessage($room, $user);
            }
        }
        $this->sendCpLovelyMessage($room, $user);

        return true;
    }

    public function handleCpRoomHistory($user, $room, $index1, $index2, $userOtherId)
    {
        $cpRoomHistory = CpRoomHistory::where("user_one_id", $user->id)
            ->where("user_two_id", $userOtherId)
            ->orWhere(function ($query) use ($user, $userOtherId) {
                $query->where("user_two_id", $user->id)
                      ->where("user_one_id", $userOtherId);
            })
            ->where('room_id', $room->id)
            ->first();

        if ($cpRoomHistory) {
            $cpRoomHistory->index1 = $index1;
            $cpRoomHistory->index2 = $index2;
            $cpRoomHistory->save();
        } else {
            CpRoomHistory::create([
                'room_id' => $room->id,
                'user_one_id' => $user->id,
                'user_two_id' => $userOtherId,
                'index1' => $index1,
                'index2' => $index2,
            ]);
        }
    }

    public function sendCpLovelyMessage($room, $user)
    {
        $cpRoomHistories = CpRoomHistory::where("room_id",$room->id)->get(['index1', 'index2']);
        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        $json = $this->cpMapJson($indices);

        Common::sendToStream('SendCustomCommand', $room->id, $user->id, $json);
    }



    public function getUserNearby($index, $mode,$rowSize = 4)
    {

        if ($index % $rowSize == 0) {
            return [$index + 1];
        } elseif (($index + 1) % $rowSize == 0) {
            return [$index - 1];
        } else {
            /*if ($mode == 0 && ($index + 1) % $rowSize == 0) {
                return [$index + 1];
            }*/
            return [$index - 1, $index + 1];
        }
    }

    public function handleLeaveCp($user,$room)
    {
        $userId = $user->id;
        $this->removeUserCpInRoom($userId);
        return $this->sendCpLovelyMessage($room, $user);
    }

    /**
     * @param mixed $userId
     * @return void
     */
    public function removeUserCpInRoom(mixed $userId): void
    {
        CpRoomHistory::where("user_one_id", $userId)
            ->orWhere("user_two_id", $userId)->delete();
    }



}
