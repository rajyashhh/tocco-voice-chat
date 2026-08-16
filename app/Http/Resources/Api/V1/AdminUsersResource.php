<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUsersResource extends JsonResource
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
            'id' => $this->id,
            'name' => @$this->user->name ?? '', // both
            'uuid' => @$this->user->uuid ?? 0,
            'agency_count' => @$this->agencyCount  ?? 0,
            'salary' => @$this->salary ?? 0,


        ];
    }
}
