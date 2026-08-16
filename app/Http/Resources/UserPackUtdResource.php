<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPackUtdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
           
                'id' => $this->id ?? 0,
                'total_price' => @$this->price ?? 0,
                'image' => @$this->ware?->img2 ?? '',
                'show_image' => @$this->ware?->show_img ?? '',
                'qty' => $this->num,
                'price' => $this->price_item,
        ];
    }
}
