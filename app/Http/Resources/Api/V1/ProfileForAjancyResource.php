<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileForAjancyResource extends JsonResource
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
            // 'gender'=>$this->gender == 1 ? __ ('male') : __ ('female'),
            // 'age'=>Carbon::parse ($this->birthday)->age,
            // 'country'=>$this->country?:''
        ];
    }
}
