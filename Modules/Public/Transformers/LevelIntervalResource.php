<?php

namespace Modules\Public\Transformers;


use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelIntervalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'max'  => $this->max,
            'min' => $this->min,
            'rewards' =>RewardLevelIntervalResource::collection($this->rewards),
        ];
    }
}
