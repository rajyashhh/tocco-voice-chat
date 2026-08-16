<?php

namespace App\Http\Resources\Api\V1;

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
use App\Http\Resources\Api\V1\MangerTypeResource;
class UserResourceSerche extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $agency_joined = $this->agency;
        if ($agency_joined != null) {
            $owner = $agency_joined->app_owner_id == $this->id ? new \stdClass() : new MiniUserResource($agency_joined->owner);
            if ($this->agency != null) {
                $agency_joined = [
                    'id'=>$this->agency->id,
                    'name'=>$this->agency->name,
                    'status'=>$this->agency->status,
                    'owner'=>$owner,
                ];
            } else {
                $agency_joined = null;
            }
        }

        $pass_status = false;
        $now_room = $this->room;
        if ($now_room){
            if($now_room->room_pass){
                $pass_status = true;
            }
        }
        $dress_1_data = $this->getUserDress(4, $this->owner?->dress_1, 'img2');
        $dress_1_fallback = $this->getUserDress(4, $this->owner?->dress_1, 'img1');
        $frame = $dress_1_data ?: $dress_1_fallback;
        if ($this->relationLoaded('chatRoomsAsUser') || $this->relationLoaded('chatRoomsAsUser2')) {
            $chatRoom = $this->chatRoomsAsUser->first() ?? $this->chatRoomsAsUser2->first();
        }
        $data = [
            'id'=>@$this->id, // both
            'uuid'=>@$this->uuid, // both
            'special_color'    => @$this->color_id ??'',
            'is_gold_id'=>(bool) @$this->is_gold_id, // both
            'name'=>@$this->name?:'', // both
            'is_follow' => $this->is_follow,
            'now_room'=>[
                'is_in_room'=>@$this->now_room_uid != 0,
                'uid'=>@(integer)$this->now_room_uid,
                'is_mine'=>@$this->id == $this->now_room_uid,
                'password_status'=>$pass_status
            ], // user data

            'profile'=>new ProfileResourceSerche(@$this->profile), // both       ------- img type   oge    contry   reqouerd
           // 'level'=>Common::level_centerSerch (@$this->id), // both
           'level' => [
                'receiver_img' => $this->receiverLevel?->img,
                'sender_img'   => $this->senderLevel?->img,
            ],
            'vip'=>@Common::ovip_center ($this->resource), // both
            'is_agent'=>$this->is_agent, // both
            'has_color_name' => $this->hasPackOfType(18), // both
            'country_hidden' => $this->hasPackOfType(13), // both
            'type_user'            => intval(@$this->type_user) ?: 0, // both
            "manger_type"          =>new MangerTypeResource(@$this->mangerType),
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'country'          =>  @$this->country ?? (object)[],
            'image_color'          => @$this->color_image,
            'frame' => $frame,
            'chat_id' => $chatRoom->id ?? null,
            'deleted_at' => $this->deleted_at,
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
        ];
        return $data;
    }


    public function getUserDress($type, $dress, $item = 'img1')
    {
        $pack = $this->packs->where('is_used', 1)
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();
        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }

}
