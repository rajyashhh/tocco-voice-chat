<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResourceSerche extends JsonResource
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
            'image'=>$this->avatar?:'',
            'gender'=>intval($this->gender),
            'age'=>Carbon::parse ($this->birthday)->age != 0 ? Carbon::parse ($this->birthday)->age : 2000,
            'country'=>$this->country?:''
        ];
    }
}
