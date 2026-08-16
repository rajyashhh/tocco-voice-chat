<?php

namespace Modules\CP\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyCpGiftsResource extends JsonResource
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
            'id' => $this->id,
            'name' => app()->getLocale() == 'ar' ? $this->name : $this->e_name,
            'image' =>  $this->img ?? '',
            'price' => $this->price ?? '',
            'show_img' => $this->show_img ?: '',
            'show_img2' => $this->show_img2 ?: '',

        ];
    }
}
