<?php

namespace Modules\Events\Transformers;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeRewardsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $value=0;
        $name='';
        $image='';
        if ($this->type == 'ware'){
            $dataa = Ware::query()->select("id",'name','show_img')->find($this->target);
            $name=$dataa?->name;
            $image=$dataa?->show_img;
        }elseif ($this->type == 'vip'){
            $dataa = OVip::query()->select("id",'name','img')->find($this->target);
            $name=$dataa?->name;
            $image=$dataa?->img;
        }else{
            $value = $this->target;
        }

        return [
            'id'        =>  $this->id,
            'type'      =>  $this->type,
            'name'      =>  $name,
            'image'     =>  $image,
            'value'     =>  $value,
        ];
    }
}
