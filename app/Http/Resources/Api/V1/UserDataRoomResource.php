<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDataRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $room = optional($this?->room);

        $pass_status = $room && $room->room_pass ? true : false;

        return [
            'is_in_room'      => (bool) $this->now_room_uid, 
            'uid'             => (int) $this->id,
            'is_mine'         => $this->id === $this->id, 
            'password_status' => $pass_status,
            "id"              => optional($room)->id,
            "room_name"       => optional($room)->room_name,
            "room_cover"      => optional($room)->room_cover,
            "room_background" => optional($room)->final_room_image,
            "mode"            => optional($room)->mode,
            'giftPrice'       => optional($room)->session_string,
            'room_type'       => optional($room)->type,
            'is_live'         => (bool) optional($room)->is_live,
        ];
    }
}
