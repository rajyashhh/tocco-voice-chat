<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppearChargerAgencyResource extends JsonResource
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
            'uuid' => $this->uuid ?? 0,
            'image' => $this->profile?->avatar ?? '',
            'agency_coins' => @$this->agency->coins ?? 0,
            'agency_name' => $this->agency->name ?? '',
            'agency_image' => $this->agency->img ?? '',
            'name' => $this->name ?? '',
            'phone' => $this->phone ?? '',
            'agency_id' => $this->agency_id,
            'status' => $this->appear_charger_agency,
        ];
    }
}
