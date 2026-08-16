<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $pass_status = false;
        $now_room    = @$this->myroom;
        // return $now_room;
        if ($now_room) {
            if ($now_room->room_pass) {
                $pass_status = true;
            }
        }
        return [
            'is_in_room'      => @$this->now_room_uid != 0,
            'uid'             => @(int)$this->id,
            'is_mine'         => @$this->id == $this->id,
            'password_status' => $pass_status,
            "id"              => @$now_room->id,
            "room_name"       => @$now_room->room_name,
            "room_cover"      => @$now_room->room_cover,
            "room_background" => @$now_room->final_room_image,
            "mode"            => @$now_room->mode,
            'giftPrice'       => @$now_room->session_string,
            'room_type'       => @$now_room->type,
            'is_live'       => (boolean)@$now_room->is_live,
        ];

        // return [
        //     'is_in_room'      => $this->now_room_uid != 0,
        //     'uid'             => (int) $this->now_room_uid,
        //     'is_mine'         => $this->id == $this->now_room_uid,
        //     'password_status' => $this->password_status,
        //     "id"              => $this->id,
        //     "room_name"       => $this->room_name,
        //     "room_cover"      => $this->room_cover,
        //     "room_background" => $this->final_room_image,
        //     "mode"            => $this->mode,
        //     'giftPrice'       => $this->session_string,
        // ];
    }
}
