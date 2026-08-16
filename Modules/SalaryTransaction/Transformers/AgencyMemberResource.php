<?php

namespace Modules\SalaryTransaction\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            
        
                'id' => $this->id ?? 0,
                'uuid' => $this->uuid ?? '',
                'name' => @$this->name ?? '',
                'image' => @$this->profile?->avatar ?? '',
       
        ];
    }
}
