<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceTokenResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => @$this->id ?? 0,
            'uuid' => @$this?->user?->uuid ?? "",
            'name' => @$this?->user?->name ?? '',
            'device_token' => @$this->device_token ?? '',

        ];
    }
}
