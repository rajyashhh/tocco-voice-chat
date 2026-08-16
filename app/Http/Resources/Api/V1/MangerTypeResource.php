<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class MangerTypeResource extends JsonResource
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
            'id'=> @$this->id ?? 0,
            'name'=>app()->getLocale() == "ar" ? @$this->name_ar ?? '' : @$this->name_en ?? '',
            'img'=>@$this?->img ?? '',
            'description'=>app()->getLocale() == "ar" ? @$this->description_ar ?? '' : @$this->description_en ?? ''
        ];
    }
}
