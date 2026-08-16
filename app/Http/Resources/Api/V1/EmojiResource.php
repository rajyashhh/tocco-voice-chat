<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class EmojiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $name = app()->getLocale() == 'ar' ? ($this->name ?? $this->name_en) : ($this->name_en ?? $this->name);
        return [
            "id" => $this->id,
            "pid" => $this->pid,
            "name" => $name,
            "emoji" => $this->emoji,
            "t_length" => $this->t_length ?? 0,
            "sort" => $this->sort,
            'type' => $this->image_type ?? 'svga',

        ];
    }
}
