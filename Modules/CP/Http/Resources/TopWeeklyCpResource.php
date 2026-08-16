<?php

namespace Modules\CP\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopWeeklyCpResource extends JsonResource
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
            'user_one_id' => $this->cp?->fromUser?->id ?? 0,
            'user_one_name' => $this->cp?->fromUser?->name ?? '',
            'user_one_image' => $this->cp?->fromUser?->profile?->avatar ?? '',
            'user_two_id' => $this->cp?->toUser?->id ?? 0,
            'user_two_name' => $this->cp?->toUser?->name ?? '',
            'user_two_image' => $this->cp?->toUser?->profile?->avatar ?? '',
            'totalGiftNum' => numToString(intval(@$this->totalGiftNum)) ?? "0",
        ];
    }
}
