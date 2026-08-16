<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */




    public function toArray($request)
    {
        $owner = $this->app_owner_id == $this->id ? new \stdClass() : new MiniUserResource($this->owner);
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'status' => $this->status,
            'image' => $this->img,
            'member_count' => count($this?->mempers),
            'owner'  => $owner,
        ];
    }
}
