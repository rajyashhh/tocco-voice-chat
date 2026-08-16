<?php

namespace App\Http\Resources\Dashboard\Users;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Facades\UserHandling;
use App\Traits\Dashboard\DashBoardTrait;

class UsersResource extends JsonResource
{
    use DashBoardTrait;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $can_play = UserHandling::chickLevelToPlay($this->resource , null) ;
        $targets =$this->targets()->orderBy('created_at', 'desc')->get()->map(function ($target) {
            $target = $target->only([
                    'id',
                    'add_month',
                    'add_year',
                    'target_usd',
                    'target_hours',
                    'target_days',
                    'target_agency_share',
                    'user_diamonds',
                    'user_hours',
                    'user_days',
                    'user_obtain',
                    'agency_obtain',
                    'updated_at'
            ]);
            return $target;
        });
       
        return [
            'id'   => $this->id,
            'img' => @$this->profile->avatar,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'online' => $this->online,
            'type' => $this->user_type($this->type_user) ,
            'coins' => $this->coins,
            'nickname' => $this->nickname,
            'charge_status' => $this->charge_status,
            'can_play' =>  $can_play,
            'reals_count' =>  $this->reals_count ?? count($this->reals),
            'moment_count' =>  $this->moments_count ?? count($this->moments),
            'total_days' =>  $this->total_days,
            'total_hours' =>  $this->live_time_sum_hours ?? $this->liveTime->sum("hours"),
            'agency_id' =>  $this->agency_id,
            'appear_charger_agency' =>  $this->appear_charger_agency,
            'targets' =>  $targets,
        ];
    }
}
