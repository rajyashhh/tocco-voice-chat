<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session' => $this->session,
            //'gifts' => GiftRoomResource::collection($this->gifts),
            'top_user_send_gifts' => [
                'id'    => $this->topUserGift->id ?? 0,
                'name'  => $this->topUserGift->name ?? '',
                'uuid'  => $this->topUserGift->uuid ?? 0,
                'image' => @$this->topUserGift?->profile?->avatar ?? '',
            ],
        ];
    }
}
