<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameRankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'coins' => $this->exp,

            'user'      => [
                'id'      => $this->user?->id ?? 0,
                'uuid'    => $this->user?->uuid ?? 0,
                'name'    => $this->user?->name ?? '',
                'image'   => $this->user?->profile?->avatar ?? '',
            ]

        ];
    }
}
