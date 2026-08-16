<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class TopUsersRankResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->user_id,
            'name' => $this->user?->name ?? '',
            'uuid' => $this->user?->uuid,
            'img' => $this->user?->profile?->avatar ?? '',
            'total_gift' => (int) ($this->total_gift ?? 0),
        ];
    }
}
