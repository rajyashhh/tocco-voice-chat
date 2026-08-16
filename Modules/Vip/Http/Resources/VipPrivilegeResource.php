<?php

namespace  Modules\Vip\Http\Resources;
use App\Http\Resources\WareResource;
use Illuminate\Http\Resources\Json\JsonResource;

class VipPrivilegeResource extends JsonResource
{
    public function toArray($request)
    {

        $ware = $this->item;
        return [
            'id' => $this->id,
            'name' => app()->getLocale() == 'ar' ? $this->name : ($this->en_name ?: $this->name),
            'active' => $this->active,
            'type' => $this->type,
            "title"=> app()->getLocale() == 'ar' ? $this->title : ($this->en_title ?: $this->title),
            "img1"=> $this->img1,
            "img2"=> $this->img2,
            'item' => $ware ? new WareResource($ware) : new \stdClass(),
        ];
    }
}
