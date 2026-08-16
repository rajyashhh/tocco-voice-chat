<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SalaryTransaction\Transformers\ChargeAgentResource;

class GeneralAgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $data = [
            'id'   => @$this->id,
            'name' => @$this->name ?: '',
            'image' => @$this->img ?? '',
            'owner' => new ChargeAgentResource($this)


        ];
        return $data;
    }
}
