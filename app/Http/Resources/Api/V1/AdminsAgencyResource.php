<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminsAgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */




    public function toArray($request)
    {
        $hasColor = Common::hasInPackV2($this->user?->packs, 18, true);

        return [
            'id' => $this->user?->id ?? 0,
            'name' => $this->user?->name ?? '',
            'uuid' => $this->user?->uuid ?? '',
            'image' => $this->user?->profile?->avatar ?? '',
            'exp'   => '0',
            'image_color'          => $this->user?->color_image,
            'id_image'             => $this->user?->specialId?->ware?->show_img ?? '',
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip($this->user?->id, 18, 'color') : null),

        ];
    }
}
