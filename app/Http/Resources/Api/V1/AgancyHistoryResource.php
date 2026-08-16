<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AgancyHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

  
     
 
    public function toArray($request)
    {


        $details = Common::CurantUsdHistoryOwner($this->user_id, $this->month, $this->year);
      

        return [
            'id' => $this->user_id, 
            'uuid'=>@$this->user->uuid, // both
            'diamonds' => $this->diamond,
            'name' => $this->user->name,
            'profile'=>new ProfileForAjancyResource(@$this->user->profile), // both
            // 'usd'=>@(double)$this->pid??0, // both
            'total_used'=>$details??0, // both

               
        ];
    }
}
