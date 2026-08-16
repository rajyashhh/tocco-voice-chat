<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserVipUtdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? 0,
            'price' => @$this->price ?? 0,
            'level' => @$this?->level ?? 0,
            'expire' => @$this?->expire,
            'qty' => $this?->qty ?? 0,
            'total_price' => $this->total,
            'image' => $this->OVip->img ?? '',

        ];
    }
}
