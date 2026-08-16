<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserShowResource extends JsonResource
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
            'name' => @$this->name ?? '', // both
            'notice' => @$this->notice ?? '',
            'owner_id' => @$this->app_owner_id??0,
            'owner_name' => @$this->owner->name  ?? '',
            'phone' => @$this->phone ?? 0,
            'img' => $this->owner->profile->avatar ?? '',
            'user_count' => $this->userCount ?? 0,

        ];
    }
}
