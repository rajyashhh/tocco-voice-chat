<?php

namespace Modules\TribeReward\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class AgencyRankingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->agency_id,
            'remaining_exp' => $this->total_exp,
            'agency'        => [
                'id'    => $this->agency->id ?? null,
                'name'  => $this->agency->name ?? '',
                'image' => getImagePath($this->agency->img ?? null),
            ],
        ];
    }
}
