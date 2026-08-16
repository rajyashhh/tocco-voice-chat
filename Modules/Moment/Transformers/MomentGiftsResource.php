<?php

namespace Modules\Moment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MomentGiftsResource extends JsonResource
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
            'gift_id' => $this->pivot->gift_id,
            'img' => $this->img,
            'num_gift' => $this->pivot->num,
        ];
    }
}
