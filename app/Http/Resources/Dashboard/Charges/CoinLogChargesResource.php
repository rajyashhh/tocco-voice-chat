<?php

namespace App\Http\Resources\Dashboard\Charges;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinLogChargesResource extends JsonResource
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
            'user' => $this->user,
            'trx' => $this->trx,
            'status' => $this->status === 1 ? 'success' : 'failed' ,
            'amount' => $this->obtained_coins,
            'type' => $this->method,
            'date' => $this->created_at,
        ];
    }
}
