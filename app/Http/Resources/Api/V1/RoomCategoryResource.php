<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\Police;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $name = app()->getLocale() === 'ar' ? ($this->name ?? $this->name_en) : ($this->name_en ?? $this->name);
        return [
            'id' => $this->id,
            'name' => $name,
            'img' => $this->img
        ];
    }
}
