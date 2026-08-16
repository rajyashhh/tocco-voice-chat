<?php

namespace App\Http\Resources\Dashboard\Users;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Facades\UserHandling;
use App\Http\Resources\Dashboard\Families\AdminFamiliesResource;
use App\Http\Resources\Dashboard\Room\AdminRoomsResource;
use App\Models\Family;
use App\Traits\Dashboard\DashBoardTrait;

class SingleUserResource extends JsonResource
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

        if($this->email && $this->phone){
            $Communication = 'Email , Phone' ;
        }
        else if ($this->email)
        {
            $Communication = 'Email' ;
        }
        else if ($this->phone)
        {
            $Communication = 'Phone' ;
        }

        if($this->google_id || $this->apple_id || $this->huawei_id){
            $Connected_Accounts = true;
        }
        else{
            $Connected_Accounts = false;
        }

        $overview = [
            'family' => new AdminFamiliesResource($this->user_family),
            'room' => new AdminRoomsResource( $this->ownerRoom),
            'levels' =>[
                'receiver_img' => @$this->getImageReceiverOrSender('receiver_id',1)->img,
                'sender_img' => @$this->getImageReceiverOrSender('sender_id',2)->img,
            ],
            'followers'  =>  $this->followers->count(),
            'followeds'  =>  $this->followeds->count(),
            'moments'    =>  $this->moments->count(),
            'reals'      =>  $this->reals->count(),
        ];

        return [
            'overview'            => $overview,
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'name'                => $this->name,
            'phone'               => $this->phone,
            'bio'                 => $this->bio,
            'charge_status'       => (int)$this->charge_status,
            'can_play'            =>  $can_play,
            'img'                 =>  @$this->profile->avatar,
            'agency_id'           =>  $this->agency_id,
            'targets'             =>  $targets,

            'country'             => $this->country,
            'Communication'       => $Communication  ,
            'email_verified_at'   => $this->email_verified_at,
            'Connected_Accounts'  => $Connected_Accounts,

            'google_id'           =>$this->google_id,
            'huawei_id'           =>$this->huawei_id,
            'apple_id'            =>$this->apple_id,

            'coins'               =>$this->coins,
            'diamond'             =>$this->total_diamond_received,
            'salary'              =>$this->salary,
            'sender_level'        =>$this->sender_level +$this->sub_sender_level,
            'receiver_level'      =>$this->received_level + +$this->sub_receiver_level,

            'type'                => $this->user_type($this->type_user) ,
            'type_id'             => $this->type_user ,

        ];
    }
}
