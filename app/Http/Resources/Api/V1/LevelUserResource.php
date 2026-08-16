<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LevelUserResource extends JsonResource
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
            'total_sender_level' => $this->total_sender_level ?? 0,
            'total_received_level' => $this->total_received_level ?? 0,
        ];
    }
}
