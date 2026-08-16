<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NowRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // now_room_uid is written on room enter and survives app kills, so on
        // its own it lies for tens of thousands of offline users (37K stale
        // rows measured 2026-06-11) — the تتبع badge showed users "in a room"
        // who actually left yesterday. Trust only the reconciled presence
        // table (room_visitors: webhooks + rooms:sync-occupancy keep it
        // truthful). now_room_uid is the room OWNER's uid, hence the join.
        $now_room = @$this->room;
        $actuallyInRoom = $this->now_room_uid &&
            $now_room &&
            \Illuminate\Support\Facades\DB::table('room_visitors')
                ->where('user_id', $this->id)
                ->where('room_id', $now_room->id)
                ->exists();
        if (!$actuallyInRoom) {
            return [];
        }

        $pass_status = false;
        if ($now_room->room_pass) {
            $pass_status = true;
        }

        if (!@$now_room->is_live &&  @$now_room->type  == 'live') {
            return [];
        }


        return [
            'is_in_room'      => @$this->now_room_uid != 0,
            'uid'             => @(int)$this->now_room_uid,
            'is_mine'         => @$this->id == @$this->now_room_uid,
            'password_status' => $pass_status,
            "id"              => @$now_room->id,
            "room_name"       => @$now_room->room_name,
            "room_cover"      => @$now_room->room_cover,
            "room_background" => @$now_room->final_room_image,
            "mode"            => @$now_room->mode,
            'giftPrice'       => @$now_room->session_string,
            'room_type'       => @$now_room->type ?? '',
            'is_live'       => (boolean)@$now_room->is_live ?? 0,
        ];

    }
}
