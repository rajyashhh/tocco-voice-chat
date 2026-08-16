<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinLogLuckyGift extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_uid'      =>$this->uuid,
            'user_name'     =>$this->user_name,
            'gift_image'    =>$this->gift_img,
            'gift_name'     =>$this->gift_name,
            'number'        =>$this->total_number,
            'cost'          =>$this->total_number * $this->gift_price,
            'win'           =>$this->total_number_win * $this->gift_price,
        ];
    }
}
