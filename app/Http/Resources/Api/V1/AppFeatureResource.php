<?php

namespace App\Http\Resources\Api\V1;
use Illuminate\Http\Resources\Json\JsonResource;

class AppFeatureResource extends JsonResource
{
    public function toArray($request)
    {
        $name = app()->getLocale() === 'ar' ? $this->name_ar ?? $this->name : $this->name;
        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'status' => (int)$this->status,
        ];
    }

}
