<?php

namespace Modules\CP\Traits;

use App\Helpers\Common;
use Modules\CP\Entities\CpRoomHistory;

trait CpHandlingTrait
{
    public function removeAllRoomCp(int $roomId, bool $isSendRtm = false) : mixed
    {
        CpRoomHistory::where("room_id", $roomId)->delete();
        $cpMapJson = $this->cpMapJson([]);

        if (!$isSendRtm) {
            return $cpMapJson;
        }

        else Common::sendToStream('SendCustomCommand', $roomId, auth()->id(), $cpMapJson);

        return true;
    }


    /**
     * @param $indices
     * @return false|string
     */
    public function cpMapJson($indices): string|false
    {
        $ms = [
            'messageContent' => [
                "message" => "cpLovelyZego",
                "data" => $indices,
            ]
        ];
        $json = json_encode($ms);
        return $json;
    }
}
