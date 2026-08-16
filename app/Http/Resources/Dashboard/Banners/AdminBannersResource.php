<?php

namespace App\Http\Resources\Dashboard\Banners;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBannersResource extends JsonResource
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
            'img'       => $this->image_url,
            'expire'    => $this->expire,
            'enable'    => $this->is_active,
        ];
    }
}
