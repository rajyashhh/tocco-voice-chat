<?php

namespace Modules\Events\Transformers;


use Illuminate\Http\Resources\Json\JsonResource;

class PkGiftResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'top_1' => WeeklyStarGift::collection($this->where("level", 1)),
            'top_2' => WeeklyStarGift::collection($this->where("level", 2)),
            'top_3' => WeeklyStarGift::collection($this->where("level", 3)),
        ];
    }
}