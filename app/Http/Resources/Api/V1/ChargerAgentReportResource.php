<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargerAgentReportResource extends JsonResource
{
    public function toArray($request)
    { 
        $user_id = $request->user()->id;
        return [
            'id' => $this->id, 
            'name' => ($this->charger_id == $user_id ? $this->user?->name :  $this->sender?->name) ?? "dashbord", 
            'order_num' => $this->id, 
            'value' => (double) $this->amount, 
            'created_at' => $this->created_at,  
        ];
    }
}
