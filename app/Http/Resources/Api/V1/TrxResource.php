<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TrxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $statuses = [
            0 => 'pending',
            1 => 'success',
            2 => 'canceled',
            3 => 'failed',
        ];

        return [
            'id' => $this->id,
            'usd' => $this->paid_usd,
            'coins' => $this->obtained_coins,
            'method' => $this->method,
            'status' => $statuses[$this->status],
            'trx_num' => $this->trx,
            'date' => Carbon::parse($this->created_at)->format('Y/m/d H:i:s')
        ];
    }
}
