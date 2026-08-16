<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Facades\UserHandling;
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

class GetUserDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $f = new \stdClass();
        $fn = '';
        $family = Family::query ()->where ('id',@$this->family_id)->first ();
        if ($family){
            $f = [
                'name'=>$family->name,
                'max_num'=>$family->num,
                'img'=>$family->image,
                'members_num'=>$family->members_count,
                'level'=>$family->level
            ];
            $fn = $family->name;
        }


        $data = [
            'id'   => @$this->id,
            'uuid' => @$this->uuid,
            'name' => @$this->name ? : '',
            'number_of_fans' => $this->numberOfFans(),
            'bio' => @$this->bio,
            'profile'=> [
                'image' => @$this->profile->avatar,
                'age' => Carbon::parse (@$this->profile->birthday)->age,
//                'country' => @$this->profile->country,//mohammed
//                'country' => !Common::hasInPack ($this->id,13,true)?(@$this->country?->flag ??''):'',//milad
                'country'              => /*!Common::hasInPack($this->id, 13, true) ?*/ (@$this->country?->flag ??'') /*: ''*/,

            ],
            'frame'=>Common::getUserDress($this->id,$this->dress_1,4,'img2', true)?:Common::getUserDress($this->id,$this->dress_1,4,'img1', true),
//            'vip'=> [
//                'level' => @$this->UserVip->level, // mohammed
//            ],
            'vip'=>@Common::ovip_center ($this->id),// milad
            'level'=> [
                'receiver_img' => $this->getImageReceiverOrSender('receiver_id',1)->img,
                'sender_img' => $this->getImageReceiverOrSender('sender_id',2)->img,
            ],
            //family

            'is_family_admin'=> @$this->is_family_admin,
            'is_family_member'=> @$this->family_id?true:false,
            'family_id' => @$this->family_id,
            'is_family_owner'=> @Family::query ()->where ('user_id',$this->id)->exists (),
            'family_name'=>@$fn,
            'family_data'=>@$f,
//            'online_time'=>$this->online_time?date("Y-m-d H:i:s", $this->online_time):'',// mohammed
            'online_time'=>!Common::hasInPack ($this->id,20,true)?($this->online_time?date("Y-m-d H:i:s", $this->online_time):''):'',// milad
            'has_color_name'=>Common::hasInPack ($this->id,18, true),
            'sound_effect_color'   => UserHandling::soundEffect($this->resource),

        ];
        return $data;
    }

}
