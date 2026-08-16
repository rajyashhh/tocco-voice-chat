<?php

namespace App\Http\Resources\Dashboard\Room;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGiftsResource extends JsonResource
{
    function types($type){
        $types = [
            1=> 'normal',
            2=> 'hot',
            3=> 'country',
            4=> 'Moment',
            5=> 'Famous gifts',
            6=> 'Lucky gifts'
        ];
        return $types[$type] ?? null;
    }
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sort'           => $this->sort,
            'name'           => $this->name,
            'price'          => $this->price,
            'enable'         => $this->enable,
            'music_gift'     => $this->music_gift,
            'use_count'      => $this->use_count,
            'img'            => $this->show_img,
            'img2'           => $this->show_img2,
            'type'           => $this->types($this->type),
        ];
    }
}
