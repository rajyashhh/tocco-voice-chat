<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class WarePaddingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            'id'        =>  $this->id,
            'image'     =>  $this->show_img == null ? '' : $this->show_img,
            'img'       =>  $this->img1 == null ? '' : $this->img1,
            'padding' =>  [
                'top'    => $this->top == 0 ?  20 : $this->top,
                'left'   => $this->left == 0 ? 15 : $this->left,
                'right'  => $this->right == 0 ? 15 : $this->right,
                'bottom' => $this->bottom == 0 ? 15 : $this->bottom,
            ],

        ];
    }
}
