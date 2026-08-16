<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Models\Pk;
use Modules\Events\Entities\GeneralRole;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeCarouselResource extends JsonResource
{

    private function getUrl()
    {
        if ($this->type === 'link' || $this->event_type === 'event') {
            return $this->url ?? '';
        }

        if (in_array($this->event_type, [
            'pk_event',
            'weekly_star',
            'charge_event',
            'event_period',
            'weekly_cp'
        ])) {
            return @$this?->generalRole?->url ?? '';
        }

        return '';
    }

    private function getRoomData($ownerRoom, $pks)
    {
        if ($this->type !== 'room') {
            return [];
        }

        return [
            'room' => [
                "id"              => @$ownerRoom->id ?? 0,
                "owner_id"        => @$this->user->id ?? 0,
                "owner_uuid"      => @$this->user->uuid ?? 0,
                "room_name"       => @$ownerRoom->room_name ?? '',
                "room_cover"      => @$ownerRoom->room_cover ?? '',
                "room_background" => @$ownerRoom->final_room_image ?? '',
                "mode"            => @$ownerRoom->mode ?? 0,
                "giftPrice"       => @$ownerRoom->session_string ?? "0",
                "is_pk"           => !empty($pks[0]) && $pks[0]->end_at >= now() ? $pks[0]->status : 0,
                "show_pk"         => @$ownerRoom->is_show_pk ?? 0,
                "password_status" => !empty(@$ownerRoom->room_pass),
                "type-number"     => @$ownerRoom->room_type ?? 0,
                "type"            => @$ownerRoom->myType ?: new \stdClass(),
            ]
        ];
    }

    public function toArray($request)
    {
        $roomPass  = $this->room->room_pass ?? '';
        $ownerRoom = @$this->user?->ownerAudioRoom;
        //  $pks       = $ownerRoom ? @$ownerRoom->pks()->latest('created_at')->limit(2)->get() : null;
        $pks = $ownerRoom?->pks ?? collect();


        [$avatar, $cpAvatar, $nameOne, $nameTwo] = Common::switch_events($this->event_type);

        $data = [
            'id'         => $this->id,
            'img'        => $this->img ?? '',
            'type'       => $this->type ?? '',
            'url'        => $this->getUrl(),
            'isLocked'   => !empty($roomPass),
            'owner_id'   => $this->owner_id ?? 0,
            'avatar'     => $avatar ?? "profile/g0lEsx7Joe.jpg",
            'event_type' => $this->event_type,
            'display_at' => @$this->display_at,
            'display_discover' => @$this->display_discover,
            'display_home_top' => @$this->display_home_top,
            'display_home_middle' => @$this->display_home_middle,
            'display_live' => @$this->display_live,
            'display_country' => @$this->display_country,
            'display_in_room' => @$this->display_in_room,


        ];

        if (\Str::contains($this->display_at, 'country')) {
            $data['countries'] = $this->countriesLite;
        }
        if ($this->event_type === 'weekly_cp') {
            $data += [
                'cp_winner_name_one' => $nameOne,
                'cp_avatar_two'      => $cpAvatar ?? "profile/g0lEsx7Joe.jpg",
                'cp_winner_name_two' => $nameTwo,
            ];
        }

        return $data + $this->getRoomData($ownerRoom, $pks);
    }
}
