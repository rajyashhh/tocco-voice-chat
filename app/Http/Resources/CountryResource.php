<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
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
            'id' => @$this->id ?? 0,
            'name' => (app()->getLocale() == 'ar' ? (@$this->name ?: '') : (@$this?->e_name ?? '')),
            'flag' => @$this->flag ?: '',
            'lang' => @$this->language ?: '',
            'phone_code' => @$this->phone_code ?: '',
            'iso' => @$this->iso ?: '',
            'total_rooms' => $this->whenHas('total_rooms'),
        ];
    }
}
