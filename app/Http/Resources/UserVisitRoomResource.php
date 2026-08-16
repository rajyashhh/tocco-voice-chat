<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class UserVisitRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            'id'        =>  $this->id,
            'name'      =>  $this->room->room_name ?? '',
            'image'      => $this->room->room_cover ?? '',
            'owner'      => [
                'id'      => @$this->room?->owner?->id ??0,
                'uuid'    => @$this->room?->owner?->uuid ??0,
                'name'    => @$this->room?->owner?->name ?? '',
                'image'   => @$this->room?->owner?->profile?->avatar ?? '',
            ]

        ];
    }
}
