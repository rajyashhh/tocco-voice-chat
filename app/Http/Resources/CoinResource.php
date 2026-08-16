<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinResource extends JsonResource
{
    public function toArray($request)
    {
        $coin_per_usd = $this->usd ? $this->coin / $this->usd : 0;

        return [
            'id' => $this->id,
            'usd' => $this->usd,
            'coin' => $this->coin,
            'first_charge_coin' => $this->first_charge_coin,
            'status' => $this->status,
            'discount_code' => $this->discount_code,
            'discount_code_expire_in' => $this->discount_code_expire_in,
            'extra_value' => $this->extra_value,
            'extra_value_end_in' => $this->extra_value_end_in,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'sort' => $this->sort,
            'payment_gateway_id' => $this->payment_gateway_id,
            'most_used' => $this->most_used ?? false,
            'coin_per_usd' => round($coin_per_usd, 2),
        ];
    }
}
