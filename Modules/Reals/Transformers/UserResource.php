<?php

namespace Modules\Reals\Transformers;

use App\Helpers\Common;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\MangerTypeResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $pass_status = false;
        $now_room = Room::query ()->where ('uid',$this->id)->first ();
        if ($now_room){
            if($now_room->room_pass){
                $pass_status = true;
            }
        }

        return [
            'id'=>@$this->id, // both
            'name'=>@$this->name?:'', // both
            'image'=>$this->profile?->avatar?:'', // both
//            'is_follow'=> $this->followeds_exists ?? false,
            'uuid'                 => @$this->uuid, // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'is_follow'=> @(bool)Common::IsFollow (@$request->user ()->id,$this->id),
            'vip_level' => (int)(@$this->UserVip->level ?? 0),
            'sender_level' => (int)(@$this->total_sender_level ?? 0),
            'now_room'=>[
                                'is_in_room'=>@$this->now_room_uid != 0,
                                'uid'=>@(integer)$this->now_room_uid,
                                'is_mine'=>@$this->id == $this->now_room_uid,
                                'password_status'=>$pass_status
                            ],
            'type_user'            => intval(@$this->type_user) ?: 0, // both
            "manger_type"          =>new MangerTypeResource(@$this->mangerType)
        ];
    }
}
