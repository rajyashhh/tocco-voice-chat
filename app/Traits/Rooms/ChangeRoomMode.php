<?php

namespace App\Traits\Rooms;

use App\Models\Room;
use Illuminate\Http\Request;

trait ChangeRoomMode
{
    /**
     * @param Room $room
     * @param int $owner_id
     * @param string $image
     * @return false|string
     */
    public function changeBackground(Room $room, int $owner_id, string $image = ''): string|false
    {
        $data = [
            "messageContent" => [
                "message"       => "changeBackground",
                "imgbackground" => $image ?: "",
                "roomIntro"     => $room->room_intro ?: "",
                "roomImg"       => $room->room_cover ?: "",
                "room_type"     => @$room->myType->name ?: "",
                "room_name"     => @$room->room_name ?: ""
            ]
        ];
        $json = json_encode($data);
        //        Common::sendToStream('SendCustomCommand', $room->id, $owner_id, $json);
        return $json;
    }
}
