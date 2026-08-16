<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPackVipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'packs' => UserPackUtdResource::collection($this->packsUser),
            'vip' => UserVipUtdResource::collection($this->userHaveVip),
        ];
    }
}
