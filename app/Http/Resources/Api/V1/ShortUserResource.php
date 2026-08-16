<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\UserPackHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (!@$this->id) {
            return;
        }
        $colorName = UserPackHelper::getColorName($this->resource);

        $data = [
            'id' => @$this->id,
            'uuid' => @$this->uuid_v2,
            'name' => @$this->name ?: '',
            'profile' => [
                'image' => $this->profile?->avatar ?: '',
                'image_id' => $this->profile?->image_id ?: '',
            ],
            'frame' => UserPackHelper::getFrameImage($this->resource),
            'frame_id' => UserPackHelper::getFrameId($this->resource),
            'has_color_name' => (bool)$colorName,
            'colored_name' => $colorName ,

        ];

        return $data;
    }

    public function getUserDress($type, $dress, $item = 'img1')
    {

        $packs = $this->packs;
        /** @var \Illuminate\Database\Eloquent\Collection $packs */
        $pack = $packs->where('is_used', 1)->where('type', $type)->where('target_id', $dress)->first();
        if ($pack) {
            if ($pack->ware) {
                return $pack->ware->{$item};
            }
        }
        return '';
    }
}
