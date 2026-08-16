<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinDiReportResource extends JsonResource
{
    public function toArray($request)
    { 
        return [
            'id' => $this->id, 
            'name' => "الماسات", 
            'img' => "", 
            'value' =>  $this->value , 
            'created_at' => $this->created_at,  
        ];
    }
}
