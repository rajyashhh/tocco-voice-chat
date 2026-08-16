<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AgencyJoinReqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $statuses = [
            0=>'pending',
            1=>'accepted',
            2=>'denied',
        ];
        return [
            'agency'=>$this->agency,
            'status'=>$statuses[$this->status?:0],
        ];
    }
}
