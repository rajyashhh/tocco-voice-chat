<?php

namespace App\Http\Resources\Dashboard\Wares;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name_en'       => $this->name_en,
            'name'          => $this->name,
            'title_en'      => $this->title_en,
            'title'         => $this->title,
            'price'         => $this->price,
            'value'         => $this->value,
            'level'         => $this->level,
            'enable'        => $this->enable,
            'level'         => $this->level,
            'num'           => $this->num,
            'img'           => $this->show_img,
            'img2'          => $this->img2,
            'expire'          => $this->expire,
            'type_id'       => $this->type,
            'get_type_id'   => $this->get_type,
        ];
    }
}
