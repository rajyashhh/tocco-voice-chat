<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // vips lookup comes from the cache layer (10 min TTL) — a cheap hit
        // per row, never a DB query, and it refreshes on its own schedule.
        $vipsData = Common::getVipsDataCached();

        $packs = $this->whenLoaded('packs', fn() => $this->packs, collect());

        $hasColor = Common::hasInPackV2($packs, 18, true);

        $coloredName = $hasColor ? Common::wareUserVipV2($this->resource, 18, 'color') : null;

        return [
            'id'   => @$this->id,
            'uuid' => @$this->uuid,
            'name' => @$this->name ?: '',
            'image' => @$this->profile->avatar ?? '',
            'image_color'          => @$this->color_image,
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'level' => Common::level_center_cached($this->resource, $vipsData),

            'colored_name' => (fn($c) => is_string($c) ? $c : '')($coloredName),
        ];
    }
}
