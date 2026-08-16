<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomRankingResource extends JsonResource
{
    
    public function toArray($request)
    {

        $data = [
            'id' => $this->ranker?->id,
            'exp' => $this->total_gifts,
            'owner' => [
                'id' => $this->ranker?->uid ?: 0,

                'uuid' => $this->ranker?->owner?->uuid_v2 ?: 0,
                'name' => $this->ranker?->owner?->name ?: '',
                'name' => $this->ranker?->owner?->name ?: '',
                'image' => $this->ranker?->owner?->profile?->avatar ?: '',
            ],
            'name' => $this->ranker?->room_name ?: '',
            'image' => $this->ranker?->room_cover ?? '',
        ];
        return $data;
    }
}
