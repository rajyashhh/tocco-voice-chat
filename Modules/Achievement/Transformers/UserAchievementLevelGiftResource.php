<?php

namespace Modules\Achievement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;


class UserAchievementLevelGiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'gift_name' => $this->gift->name,
        ];
    }
}