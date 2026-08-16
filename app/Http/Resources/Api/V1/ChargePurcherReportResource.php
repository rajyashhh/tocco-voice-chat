<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargePurcherReportResource extends JsonResource
{
    public function toArray($request)
    { 
        $name = '';
        if ($this->method == "google_pay") {
            $name = 'Google Pay';
        }elseif ($this->method == "huawei_pay") {
            $name = 'Huawei Pay';
        }elseif ($this->method == "apple_pay") {
            $name = 'Apple Pay';
        }
        return [
            'id' => $this->id, 
            'name' => $name, 
            'order_num' => $this->trx, 
            'value' => (double) $this->obtained_coins, 
            'created_at' => $this->created_at,  
        ];
    }
}
