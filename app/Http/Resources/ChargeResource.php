<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $img = ($this->user_type == 'dash') ? Agency::find($this->agency_id)?->img : $this->user?->profile?->avatar;

        return [
            'id' => $this->id,
            'uuid' => @$this->user?->uuid ?? 0,
            'user_id' => @$this->user?->id ?? 0,
            'coins' => $this->amount,
            'type' => $this->user_type == 'dash' ? 'agency' : 'user',
            'created_at' => $this->created_at,
            'img' => getDriverUrl() . '/' . $img,
        ];
    }
}
