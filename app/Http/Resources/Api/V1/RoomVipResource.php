<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomVipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type ?? 0,
            'img' => $this->img ?? "",
            'exp' => $this->exp ?? "",
            'level' => $this->level ?? "",
            'name_en' => $this->name_en ?? "",
            'name_ar' => $this->name_ar ?? "",
            'updated_at' => $this->updated_at ?? now(),
            'created_at' => $this->created_at ?? now(),
            'id' => $this->id ?? 0,
        ];
    }
}
