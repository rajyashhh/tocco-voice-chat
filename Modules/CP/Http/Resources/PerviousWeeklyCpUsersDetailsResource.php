<?php

namespace Modules\CP\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PerviousWeeklyCpUsersDetailsResource extends JsonResource
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
            'user_one_id' => $this->userOne?->id ?? 0,
            'user_one_name' => $this->userOne?->name ?? '',
            'user_one_image' => $this->userOne?->profile?->avatar ?? '',
            'user_two_id' => $this->userTwo?->id ?? 0,
            'user_two_name' => $this->userTwo?->name ?? '',
            'user_two_image' => $this->userTwo?->profile?->avatar ?? '',
            'total_gift_price' =>numToString(intval( $this->total_price)) ?? '0',
            'level' => $this->level,


        ];
    }
}
