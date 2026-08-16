<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiverGiftLogForKickedResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $hasColor = Common::hasInPack(@$this->receiver->id, 18, true);

        $data = [
            'id' => @$this->receiver->id ?? 0, // both
            'uuid' => @$this->receiver->uuid ?? '', // both
            'name' => @$this->receiver->name ?: '', // both
            'image' => $this->receiver->profile->avatar ?: '',
            'exp'   => $this->exp ?? '',
            'image_color'          => @$this->receiver->color_image,
            'id_image'             => @$this->receiver->specialId?->ware?->show_img ?? '',
            'level' => Common::level_center_min(@$this->receiver->id), // refactor
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip(@$this->receiver->id, 18, 'color') : null),
            'is_kicked' => (bool) $this->is_kicked,

        ];

        return $data;
    }
}
