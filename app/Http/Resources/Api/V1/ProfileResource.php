<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\StorageHelper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'image' => StorageHelper::url($this->avatar),
            'image_id' => $this->whenHas('image_id') ?: '',
            'gender' => $this->gender !== null ? intval($this->gender) : null,
            'birthday' => $this->when(isset($this->birthday), $this->birthday ? Carbon::parse($this->birthday)->format('Y-m-d') : ''),
            'age' => $this->when(isset($this->birthday), $this->birthday ? Carbon::parse($this->birthday)->age : null),
            'province' => $this->whenHas('province') ?: '',
            'city' => $this->whenHas('city') ?: '',
        ];
    }
}
