<?php

namespace App\Http\Resources\Dashboard\Users;
use App\Helpers\StorageHelper;

use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Facades\UserHandling;

class AdminUsersResource extends JsonResource
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
        $mini_profile = [
            'id'   => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'img' => @$this->profile->avatar,
        ];
        $targets =$this->targets()->orderBy('created_at', 'desc')->get()->count();
         return [
            'mini_profile' => $mini_profile,
            'phone' => $this->phone,
            'online' => $this->online,
            'type' => $this->user_type($this->type_user) ,
            'coins' => $this->coins,
            'nickname' => $this->nickname,
            'charge_status' => $this->charge_status,
            'can_play' =>  $can_play,
            'targets' =>  $targets,
            'reals_count' =>  count($this->reals),
            'moment_count' =>  count($this->moments),
            'appear_charger_agency' =>  $this->appear_charger_agency,
        ];
    }
}
