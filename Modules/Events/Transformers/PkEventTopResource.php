<?php

namespace Modules\Events\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PkEventTopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isRoom = (int) $request->input('type') === 3;

        return [
            'totalGiftNum' => $this->totalGiftNum ?? 0,
            'user_id'   => $this->user?->uuid ?? 0,
            'id'   => $this->user?->id ?? 0,
            'uuid'      => $this->user?->uuid ?? 0,
            'name'      => $isRoom ? ($this->user?->ownerRoom?->room_name ?? '') : ($this->user?->name ?? ''),
            'avatar'    => $isRoom ? ($this->user?->ownerRoom?->room_cover ?? '') : ($this->user?->profile?->avatar ?? ''),
        ];
    }
}