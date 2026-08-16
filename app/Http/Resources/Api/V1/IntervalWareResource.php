<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class IntervalWareResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if ($this->type == 4) {
            $type = 'frame';
        } elseif ($this->type == 5) {
            $type = 'bubble';
        } elseif ($this->type == 6) {
            $type = 'intro';
        }
        return [
            'id' => $this->id,
            'image' => $this->show_img,
            'type' => $type,
            'name' => $this->name,
            'svga-image' => $this->img2,
        ];
    }
}
