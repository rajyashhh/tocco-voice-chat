<?php

namespace Modules\RoomBoom\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PercentageBoomResource extends JsonResource
{
    public function toArray($request)
    {
        $data =  [

            'percentage' => $this->percentage,
            'image' => $this->image ?? '',
            'image_type' => $this->image_type ?? '',
        ];

        return $data;
    }
}
