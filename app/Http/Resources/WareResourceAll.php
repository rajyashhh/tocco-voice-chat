<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class WareResourceAll extends JsonResource
{
    public function toArray($request)
    {
        if ($this->type == 25) {
            $title = $this->value;
        } else {
            $title = app()->getLocale() == 'ar' ? ($this->title ?: '') : ($this->title_en ?? '');
        }
        return [
            'id'        =>  $this->id,
            'image'     =>  $this->show_img == null ? '' : $this->show_img,
            'img'       =>  @($this->img2 == null ? $this->img1 : $this->img2) ?? '',
            'image_type' => $this->image_type ?? "",
            'key_json'  => empty($this->key_json) ? (object)[] : $this->key_json,
        ];
    }
}
