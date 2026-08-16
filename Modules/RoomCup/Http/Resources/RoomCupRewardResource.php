<?php

namespace  Modules\RoomCup\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCupRewardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'amount'      => $this->amount,
            'date'  => Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
        ];
    }
}

