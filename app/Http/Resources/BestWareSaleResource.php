<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class BestWareSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        
        {
            if ($this->type == 25) {
                $title = $this->ware->value;
            } else {
                $title = app()->getLocale() == 'ar' ? ($this->ware->title ?: '') : ($this->ware->title_en ?? '');
            }
            return [
                'id'        =>  $this->ware->id,
                'name'      =>  app()->getLocale() == 'ar' ? ($this->ware->name ?: '') : ($this->ware->name_en ?? ''),
                'title'     =>  $title,
                'price'     =>  $this->ware->price ?: 0,
                'color'     =>  $this->ware->color ?: '',
                'expire'    =>  $this->ware->expire == 0 ? 99999999 : $this->expire,
                'image'     =>  $this->ware->show_img == null ? '' : $this->show_img,
                'img'       =>  $this->ware->img1 == null ? '' : $this->ware->img1,
                'svg'       =>  $this->ware->img2 == null ? '' : $this->ware->img2,
                'video'     =>  $this->ware->img3  == null ? '' : $this->ware->img3,
                'image_type' => $this->ware->image_type ?? "",
                'type'       => $this->ware->type,
            ];
        }
    }
}
