<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TrashedUserResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => (int)$this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'delete_at' => Carbon::parse($this->delete_at)->diffForHumans(),
        ];
    }
}
