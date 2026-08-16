<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomAdminsResource extends JsonResource
{
    private $userDresses = [];

    public function toArray($request)
    {
        $this->userDresses = $this->packs->where('is_used', 1)->keyBy(fn($p) => $p->type . '_' . $p->target_id);

//        $agency = $this->whenLoaded('agency');
//        $family = $this->whenLoaded('family');
        $profile = $this->whenLoaded('profile');
//        $chatSetting = $this->whenLoaded('chatSetting');
//        $userSetting = $this->whenLoaded('userSetting');
//        $ownAgency = $this->whenLoaded('ownAgency');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name ?: 'user #' . $this->uuid,
//            'nick_name' => $this->nick_name,
//            'agency' => $this->formatAgency($agency),
//            'family_data' => $this->formatFamily($family),
            'profile' => $profile ? new ProfileResource($profile) : null,
//            'online_time' => $this->formatOnlineTime(),
//            'diamonds' => $this->total_diamond_received ?? 0,
            'vip' => $this->getVip(),
            'frame' => $this->getUserDress(4, $this->dress_1),
//            'bubble' => $this->getUserDress(5, $this->dress_2, 'show_img'),
//            'intro' => $this->getUserDress(6, $this->dress_3),
//            'intro_type' => $this->getUserDress(6, $this->dress_3, 'image_type'),
//            'chat_setting' => $chatSetting ? new ChatSettingResource($chatSetting) : null,
//            'change_room_effect' => $userSetting ? new ShowUserSettingResource($userSetting) : null,
//            'my_agency' => $ownAgency ? $ownAgency->only(['id','name','status','img','phone','url','contents']) : null,
            'level' => Common::level_center_search(@$this),
//            'level' => $this->preloaded_level ,
//            'user_types' => $this->user_types2 ?? [0],
            'special_color'    => @$this->color_id ??'',
            'has_color_name' => $this->getPackWithTypeV2(18), // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'image_color'          => @$this->color_image,
        ];
    }

//    private function formatAgency($agency)
//    {
//        if (!$agency) return null;
//
//        $owner = $agency->app_owner_id == $this->id ? new \stdClass() : new MiniUserResource($agency->owner);
//
//        return [
//            'id' => $agency->id,
//            'name' => $agency->name,
//            'status' => $agency->status,
//            'image' => $agency->img,
//            'member_count' => $agency->mempers->count() ?? 0,
//            'owner' => $owner,
//        ];
//    }

//    private function formatFamily($family)
//    {
//        return $family ? [
//            'owner_id' => $family->user_id,
//            'family_name' => $family->name,
//            'img' => $family->image,
//            'num_of_members' => $family->members_count,
//        ] : null;
//    }
//
//    private function formatOnlineTime()
//    {
//        if ($this->relationLoaded('activePack20') && $this->activePack20) {
//            return null;
//        }
//
//        return $this->org_online_time ? Carbon::createFromTimestamp($this->org_online_time)->diffForHumans() : null;
//    }

    private function getUserDress($type, $dress, $item = 'img1')
    {
        $key = $type . '_' . $dress;

        return $this->userDresses[$key]?->ware->img2 ?? $this->userDresses[$key]?->ware->img1 ?? '';
    }

    private function getVip()
    {
        static $vip = null;
        if ($vip === null) {
            $vip = Common::ovip_center_v2($this);
        }
        return $vip;
    }

//    private function getLevel()
//    {
//        static $level = null;
//        if ($level === null) {
//            $level = Common::level_center($this->id);
//        }
//        return $level;
//    }
}

