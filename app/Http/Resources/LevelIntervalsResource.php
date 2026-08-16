<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelIntervalsResource extends JsonResource
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
            'name' => $this->name,
            'min' => $this->min,
            'max' => $this->max,
            'type' => $this->type == 3 ? "room" : ($this->type == 1 ? "receiver" : "sender"),
            'created_at' => $this->created_at ,
        ];
        }
}
