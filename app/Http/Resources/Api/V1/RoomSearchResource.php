<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Pk;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Police;
use App\Http\Resources\CountryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pks = !is_null(@$this?->room_id) ? $this->getRoomTwoLastPk(@$this?->room_id) : null;
        $achievement_images = [];
        if (@$this->owner?->medals) {
            foreach (@$this->owner?->medals as $medal) {
                if ($medal->achievementLevel && $medal->achievementLevel->achievement && $medal->achievementLevel->achievement->type?->value == 'room_target') {
                    $achievement_images[] = $medal->achievementLevel->valid_image;
                }
            }
        }
        $isHideCountry = $this?->owner?->getPackWithType(13);
        $country = $this?->country
            ? new CountryResource($this?->country)
            : [
                'id' => 0,
                'name' => '',
                'flag' => '',
                'lang' => '',
                'phone_code' => ''
            ];
        $endCountry = !$isHideCountry  ?  $country: (object)[];
        $dress_1_data = $this->getUserDress(4, $this->owner?->dress_1, 'img2');
        $dress_1_fallback = $this->getUserDress(4, $this->owner?->dress_1, 'img1');
        $frame = $dress_1_data ?: $dress_1_fallback;
        return [
            'id' => $this->id ?? 0,
            'room_id' => (string) $this->id ?? '0',
            "room_name" => $this->room_name ?? '',
            "numid" => $this->numid ?? 0,
            "hot" => $this->hot ?? '',
            "room_cover" => $this->room_cover ?? '',
            "cover" => $this->room_cover ?? '',
            "room_intro" => $this->room_intro ?? '',
            "room_background" => @$this->final_room_image ?? '',
            "room_welcome" => $this->room_welcome ?? '',
            "mode" => @$this->mode ?? 0,
            'giftPrice' => @$this->session_string ?? "0",
            "show_pk"             => @$this->is_show_pk ?? 0,
            'password_status'     => !(@$this->room_pass == ""),
            'type-number'                => @$this->room_type ?? 0,
            'type' => @$this->myType ?: new \stdClass(),
            "is_pk"               => (bool)((@$pks[0]) && @$pks[0]->end_at >= now() ? @$pks[0]->status : 0),

            "room_pass" => $this->room_pass ?? '',
            "uid" => $this->uid ?? 0,
            'owner_id' =>  $this->uid ?? 0,
            'owner_uuid' => $this->owner?->uuid ?? '',
            "name" => $this->name ?? '',

            "nickname" => $this->nickname ?? '',
            'country' =>$endCountry ,
            'achievement_images' => $achievement_images,
//            'medals'               => @$this->owner?->medals()?->where('is_enable', true)->get() ?? [],
            'country_hidden' => $isHideCountry,
            'frame' => $frame,
            'room_type' => $this->type,

        ];
    }

    private function getRoomTwoLastPk(int $roomId)
    {
        return Pk::query()
            ->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();
    }

    public function getUserDress($type, $dress, $item = 'img1')
    {
        $pack = $this->packs?->where('is_used', 1)
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();
        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }
}
