<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftLogReportResource extends JsonResource
{
    public function toArray($request)
    { 
        return [
            'id' => $this->id, 
            'name' => $this->gift?->name, 
            'img' => $this->gift?->img, 
            'value' => (double)$this->receiver_obtain, 
            'created_at' => $this->created_at,  
        ];
    }
}
