<?php

namespace App\Http\Resources\Dashboard\Events;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWeeklyStarEventsResource extends JsonResource
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
                return [
                    'name' => $item->name,
                    'id' => $item->id
                ];
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
                return [
                    'name' => $item->name,
                    'id' => $item->id
                ];
            }
            else{
                return '';
            }
        }
        else{
            return null;
        }
    }
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => null,
            'event_id'   => $this->weekly_star_id ,
            'type'       => $this->type,
            'level'      => $this->level,
            'expire'     => $this->expire,
            'target'     => $this->target,
            'vip'        => $this->get_name($this->target ,$this->type ),
            'ware'       => $this->get_name($this->target ,$this->type ) ,
        ];
    }
}
