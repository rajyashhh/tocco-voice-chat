<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentCoin;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class RechargeCoinsReportResource extends JsonResource
{


    public function toArray($request)
    {
        $paymentCoins = PaymentCoin::orderBy('type')->pluck('title', 'type')->toArray();

        $method = $paymentCoins[$this->method] ?? 'fawry';

        $user = auth()->user();
        return [
            'id'          => $this->user_id,
            'uuid'          => $user->uuid,
            'diamonds'    => numToStringNew($this->obtained_coins),
            'operation_no' => $this->trx,
            'created_at'  => Carbon::parse(@$this->created_at)->format('Y-m-d h:i:s A'),
            'type' => $method,
            'coins' => numToStringNew($this->obtained_coins)
        ];
    }
}
