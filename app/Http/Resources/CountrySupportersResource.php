<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class CountrySupportersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Prefer the translation file, fall back to the DB columns (the
        // countries table is seeded with ar `name` + en `e_name` for every
        // country in the world, so nothing ever renders as "countries.X").
        $key = "countries.{$this->e_name}";
        $translated = __($key);
        $localized = $translated !== $key
            ? $translated
            : (app()->getLocale() == 'ar' ? (@$this->name ?: @$this->e_name) : (@$this->e_name ?: @$this->name));

        return [
            'id' => @$this->id ?? 0,
            'name' => $localized ?: '',
            'e_name' => @$this->e_name ?: '',
            'flag' => @$this->flag ?: '',
            'lang' => @$this->language ?: '',
            'phone_code' => @$this->phone_code ?: '',
            'iso' => @$this->iso ?: '',
            'show_url' => route('countries.preview', $this->id),
            'total_rooms' => $this->whenHas('total_rooms'),
            'supporters' => $this->whenLoaded('supporters', function () {
                return $this->supporters->map(function ($supporter) {
                    return [
                        'id'     => $supporter->sender?->id ?? 0,
                        'uuid'     => $supporter->sender?->uuid ?? 0,
                        'name'   => $supporter->sender?->name ?? '',
                        'avatar' => $supporter->sender?->profile?->avatar ?? '',
                        'total'  => (int) $supporter->total_sent,
                    ];
                });
            }),
        ];
    }
}
