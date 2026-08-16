<?php

namespace App\Http\Resources\Dashboard\Events;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPKEventRewords extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_name($id , $type) {
        if($type == 'ware')
        {
            $item = Ware::find($id);
            if($item)
            {
                return $item->name;
            }
            else{
                return '';
            }
        }
        else if($type == 'vip')
        {
            $item = OVip::find($id);
            if($item)
            {
                return $item->name;
            }
            else{
                return '';
            }
        }
    }
    public function toArray(Request $request): array
    {
        $img_check = 0 ;
        $coins_check = 0 ;
        if($this->type == 'coins')
        {
            $coins_check = 0 ;
            $name = $this->target;
        }
        else  if($this->type == 'achievement'){
            $coins_check = 0 ;
            $name = $this->target;
        }
        else{
            $name = $this->get_name($this->target ,$this->type );
        }
        return [
            'id'           => $this->id,
            'expire'       => $this->expire,
            'type'         => $this->type,
            'level'         => $this->level,
            'img_check'    => $img_check,
            'coins_check'  => $coins_check,
            'name'         =>$name
        ];
    }
}
