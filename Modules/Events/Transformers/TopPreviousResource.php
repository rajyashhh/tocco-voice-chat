<?php

namespace Modules\Events\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class TopPreviousResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
{
    $name_en = 'Tik Chat';
    $name_ar = 'تيك شات';
    return [

        'user_id'   => $this->user?->id ?? 0,
        'uuid'      => $this->user?->uuid ?? 0,
        'name'      => $this->user?->name ?? (app()->getLocale() == 'ar' ?$name_ar:$name_en),
        'avatar'    => $this->user?->profile?->avatar ?? 'tik-logo.png',
        'level'     => @$this->level ?? 0,
    ];
}
}
