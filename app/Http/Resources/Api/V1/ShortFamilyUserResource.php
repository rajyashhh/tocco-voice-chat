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

class ShortFamilyUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
       if(!@$this->id){
           return ;
       }

        $data = [
            'id'=>@$this->user?->id,
           // 'is_family_admin'=>@$this->user_type == 1 ? true : false,
           'is_family_admin'=> @$this->user?->is_family_admin,
            'name'  => $this->user?->name,
//            'profile'=>new ProfileResource(@$this->profile),
            'profile'=> [
                'image' => $this->user?->profile?->avatar,
            ],
            
            'family_status' => @$this->user_type,
            'type_user'            => intval(@$this->user?->type_user) ?: 0, // both
            "manger_type"          =>new MangerTypeResource(@$this->user?->mangerType),
        ];
        return $data;
    }

    public function handelStatics($request){
        $user = $request->user();
        $visitor = 0;
        $fans = 0;
        $friends = 0;
        $income = 0;
        $frame = 0;
        $enteirs = 0;
        $bubble = 0;
        if ($request->visitor != null){
            $visitor = (integer)$user->profileVisits()->count() - (integer)$request->visitor;
        }
        if ($request->fans != null){
            $fans = (integer)$user->numberOfFans() - (integer)$request->fans;
        }
        if ($request->friends != null){
            $friends = (integer)$user->numberOfFriends() - (integer)$request->friends;
        }
        if ($request->income != null){
            $income = (integer)$user->coins - (integer)$request->income;
        }
        if ($request->frame != null){
            $frame = (integer)$user->frames_count() - (integer)$request->frame;
        }
        if ($request->enteirs != null){
            $enteirs = (integer)$user->intros_count() - (integer)$request->enteirs;
        }
        if ($request->bubble != null){
            $bubble = (integer)$user->bubble_count() - (integer)$request->bubble;
        }

        return [
            'visitor' => $visitor,
            'fans' => $fans,
            'friends' => $friends,
            'income' => $income,
            'frame' => $frame,
            'enteirs' => $enteirs,
            'bubble' => $bubble
        ];
    }
}
