<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeDiReportResource extends JsonResource
{
    public function toArray($request)
    { 
        return [
            'id' => $this->id, 
            'name' => "صرف الماس", 
            'img' => "", 
            'value' =>  - (double) $this->diamonds, 
            'created_at' => $this->created_at,  
        ];
    }
}
