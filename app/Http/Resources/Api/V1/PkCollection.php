<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PkCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
//        $t1 = User::query ()->whereIn ('id',explode (',',$this->team_1))->get ();
//        $t2 = User::query ()->whereIn ('id',explode (',',$this->team_2))->get ();

        $endTime = Carbon::parse($this->end_at);
        $currentTime = Carbon::now();
        $timeDifference = $endTime->diff($currentTime);

        if ($endTime < $currentTime) {
            // The end time has already passed, so output 0
            $remaining_time = '0:0:0';
            $h = 0;
            $m = 0;
            $s = 0;
        } else {
            $remaining_time = $timeDifference->format('%H:%I:%S');
            $h = $timeDifference->h;
            $m = $timeDifference->i;
            $s = $timeDifference->s;
        }

        return [
            'id'=>$this->id,
            'room_id'=>$this->room_id,
            'start_at'=>$this->start_at,
            'end_at'=>$this->end_at,
            'team1'=>$this->team_1,
            'team2'=>$this->team_2,
            'team1_score'=>$this->t1_score,
            'team2_score'=>$this->t2_score,
            'remaining_time'=>$remaining_time,
            'h'=>$h,
            'm'=>$m,
            's'=>$s,
            't1_scale'=>(double)$this->t1_per,
            't2_scale'=>(double)$this->t2_per,
        ];
    }
}
