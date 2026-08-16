<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class GiftCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $locale = App::getLocale();

        return [
            'id' => $this->id,
            'title' => $this->getLocalizedValue($this->title, $locale),
            'type' => $this->type,



        ];
    }

    private function getLocalizedValue($value, $locale)
    {
        // $value is cast to array in your model
        if (is_array($value)) {
            return $value[$locale] ?? ($value['en'] ?? '');
        }

        return $value; // fallback if not array
    }
}
