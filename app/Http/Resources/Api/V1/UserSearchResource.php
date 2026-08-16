<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Http\Resources\CountryResource;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Country;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\Pack;
use App\Models\Room;
use App\Models\Ware;
use Carbon\Carbon;
use http\Client\Curl\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pass_status = false;
        $now_room = Room::query ()->where ('uid',$this->now_room_uid)->first ();
        if ($now_room){
            if($now_room->room_pass){
                $pass_status = true;
            }
        }

        $data = [
            'id'=>@$this->id,
            'uuid'=>@$this->uuid,
            'special_color'    => @$this->color_id ??'',
            'name'=>@$this->name?:'',
            'visit_time' => '',
            'profile'=> [
                'image' => $this->profile?->avatar,
                'age' => Carbon::parse($this->profile?->birthday)->age,
                'gender'=>(integer)($this->profile?->gender ?? 1),
            ],
            'frame'=>Common::getUserDress($this->id,$this->dress_1,4,'img2', true)?:Common::getUserDress($this->id,$this->dress_1,4,'img1', true),
            'frame_id'=>@$this->dress_1,
            'now_room'=>[
                'is_in_room'=>@$this->now_room_uid != 0,
                'uid'=>@$this->now_room_uid,
                'is_mine'=>@$this->id == $this->now_room_uid,
                'password_status'=>$pass_status
            ],
            'vip'=> [
                'level' => @$this->UserVip->level,
            ],
            'level'=> [
                'receiver_img' => $this->getImageReceiverOrSender('receiver_id',1)->img,
                'sender_img' => $this->getImageReceiverOrSender('sender_id',2)->img,
            ],


        ];

        return $data;
    }

}
