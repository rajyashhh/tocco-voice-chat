<?php

namespace App\Http\Resources\Dashboard\Vips;

use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPrevilageVipsResource extends JsonResource
{
    use DashBoardTrait;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->type,
            'img1' => $this->img1,
            'img2' => $this->img2,
            'name_en' => $this->en_name,
            'name_ar' => $this->name,
            'title' => $this->title,
            'type' => $this->vip_previlage_type($this->type),
        ];
    }
}
