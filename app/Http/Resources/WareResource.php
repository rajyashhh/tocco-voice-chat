<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class WareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if ($this->type == 25) {
            $title = $this->value;
        } else {
            $title = app()->getLocale() == 'ar' ? ($this->title ?: '') : ($this->title_en ?? '');
        }
        return [
            'id'        =>  $this->id,
            'name'      =>  app()->getLocale() == 'ar' ? ($this->name ?: '') : ($this->name_en ?? ''),
            'title'     =>  $title,
            'price'     =>  $this->price ?: 0,
            'color'     =>  $this->color ?: '',
            'expire'    =>  $this->expire == 0 ? 99999999 : $this->expire,
            'image'     =>  $this->show_img == null ? '' : $this->show_img,
            'img'       =>  $this->img1 == null ? '' : $this->img1,
            'svg'       =>  $this->img2 == null ? '' : $this->img2,
            'video'     =>  $this->img3  == null ? '' : $this->img3,
            'image_type' => $this->image_type ?? "",
            'type'       => $this->type,
            'key_json'  => $this->key_json,
            'padding' => $this->when($this->type == 5, [
                'top'    => $this->top == 0 ?  20 : $this->top,
                'left'   => $this->left == 0 ?15: $this->left,
                'right'  => $this->right == 0 ? 15 : $this->right,
                'bottom' => $this->bottom == 0 ? 15 : $this->bottom,
            ]),

        ];
    }
}
