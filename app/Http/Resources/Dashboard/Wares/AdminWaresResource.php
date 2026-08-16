<?php

namespace App\Http\Resources\Dashboard\Wares;
use App\Helpers\StorageHelper;

use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWaresResource extends JsonResource
{

    use DashBoardTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'sort'         => $this->sort,
            'name_en'    => $this->name_en,
            'name'       => $this->name,
            'price'      => $this->price,
            'level'      => $this->level,
            'enable'     => $this->enable,
            'level'      => $this->level,
            'num'        => $this->num,
            'value'        => $this->value,
            'img'        => $this->show_img,
            'type'       => $this->ware_types($this->type),
            'get_type'   => $this->wares_main_type($this->get_type),
        ];
    }
}
