<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveAgencyResource extends JsonResource
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
            'owner' => new OwnerAgencyResource($this->owner),
            'name' => $this->name ?? '',
            'phone' => $this->phone ?? '',
            'targe' => $this->targe ?? 0,
            'img' => $this->img ?? '',
        ];
    }
}
