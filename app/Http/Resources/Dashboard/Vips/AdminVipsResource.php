<?php

namespace App\Http\Resources\Dashboard\Vips;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminVipsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'sort'      => $this->sort,
            'name'      => $this->name,
            'level'     => $this->level,
            'img'       => $this->img,
            'price'     => $this->price,
            'expire'    => $this->expire,
            'privilegs' => $this->privilegs->pluck('id')->toArray(),
        ];
    }
}
