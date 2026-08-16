<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftRoomResource extends JsonResource
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
            'id' => $this->gift->id,
            'name' => app()->getLocale() == 'ar' ? $this->gift->name : $this->gift->e_name,
            'img' => $this->gift->img ?: '',
            'show_img' => $this->gift->show_img ?: '',
            'show_img2' => $this->gift->show_img2 ?: '',
            'sender' => [
                'id'    => $this->sender->id ?? 0,
                'name'  => $this->sender->name ?? '',
                'uuid'  => $this->sender->uuid ?? 0,
                'image' => $this->sender?->profile?->avatar ?? '',
            ],
            'receiver' => [
                'id'    => $this->receiver->id ?? 0,
                'name'  => $this->receiver->name ?? '',
                'uuid'  => $this->receiver->uuid ?? 0,
                'image' => $this->receiver?->profile?->avatar ?? '',
            ],
            
        ];
    }
}
