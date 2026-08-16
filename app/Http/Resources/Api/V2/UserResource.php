<?php

namespace App\Http\Resources\Api\V2;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Http\Resources\Api\V1\ChatSettingResource;
use App\Http\Resources\Api\V1\MangerTypeResource;
use App\Http\Resources\Api\V1\MiniUserResource;
use App\Http\Resources\Api\V1\NowRoomResource;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Http\Resources\Api\V1\ShowUserSettingResource;
use App\Http\Resources\Api\V1\UserAgencyResource;
use App\Http\Resources\Api\V1\UserRoomResource;
use App\helper\UserDataHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pass_status = false;
        $now_room    = @$this->room;
        if ($now_room) {
            if ($now_room->room_pass) {
                $pass_status = true;
            }
        }

        $f      = null;
        $family = @$this->family;
        if ($family) {
            $f = [
                'owner_id'    => $family->user_id,
                'family_name' => $family->name,
                'img'         => $family->image,
                'num_of_members'     => $family->members_count,
            ];
        }

        $presence = UserDataHelper::presence($this->resource, respectPackExpiry: true);

        $frame  = $this->getUserDress(4, $this->dress_1, 'img2') ?? $this->getUserDress(4, $this->dress_1, 'img1');
        $bubble = $this->getUserDress(5, $this->dress_2, 'show_img');
        $intro  = $this->getUserDress(6, $this->dress_3, 'img2') ?? $this->getUserDress(6, $this->dress_3, 'img1');
        $introType = $this->getUserDress(6, $this->dress_3, 'image_type');

        $isHideCountry = $this->getPackWithTypeV2(13);
        $userHandling = new \App\Classes\UserHandling();
        $color_image = @$this->color_image;
        $chat_setting = \App\Models\ChatSetting::where("user_id", $this->id)->first();
        if ($chat_setting == null) {
            $chat_setting = \App\Models\ChatSetting::create([
                'user_id'     => $this->id,
                'chat_with_friends'     =>  1,
                'chat_with_all'         =>  0,
            ]);
        }
        $show_user_setting = \App\Models\UserSetting::where("user_id", $this->id)->first();
        if ($show_user_setting == null) {
            $show_user_setting = \App\Models\UserSetting::create([
                'user_id'     => $this->id,
                'show_git'    => 1,
                'show_intro'  => 1,
                'show_banner' => 1,
            ]);
        }
        $data      = [
            'id'      => @$this->id,
            'uuid'    => @$this->uuid_v2,
            'special_color'    => @$this->color_id ?? '',
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'chat_id' => @$this->chat_id ?: "",
            'notification_id'      => @$this->notification_id ?: "",
            'name'                 => @$this->name ?: 'user' . ' ' . '#' . @$this->uuid_v2,
            'nick_name'            => @$this->nick_name,
            'number_of_fans'       => $this->followers_count ?? $this->numberOfFans(),
            'number_of_followings' => $this->following_count ?? $this->numberOfFollowings(),
            'number_of_friends'    => $this->numberOfFriends(),
            'profile_visitors'     => $this->profile_visits_count ?? $this->profileVisits()->count(),
            'is_followed'            => $this->is_followed,
            'is_follow'            => $this->is_follow,
            'is_friend'            => $this->isFriends(),
            'room'             => new UserRoomResource($this),
            'now_room'             =>  new NowRoomResource($this),
            'agency'               => UserAgencyResource::make($this->agency),
            'family_id'            => @$this->family_id,
            'family_data'          => @$f,
            'profile'              => new ProfileResource(@$this->profile),

            'diamonds'             => @$this->total_diamond_received ?: 0,
            'vip'                  => @Common::ovip_center_v2($this),
            'lang'                 => @$this->lang,
            'country'              => !$isHideCountry ? ($this->country ?? (object)[]) : (object)[],
            'have_country'         => ($this->country != null),
            'medals'               =>  @$this->enabledMedals ?? [],
//            'medals'               => $this->medals()->where('is_enable', true)->get(),

            'frame'                => $frame,
            'intro'                => $intro,
            'intro_type' => $introType,
            'bubble'               => $bubble,
            'bubble_id'            => $bubble != '' ? $this->dress_2 : 0,
            'frame_id'             => $frame != '' ? @$this->dress_1 : 0,
            'intro_id'             => $intro != '' ? @$this->dress_3 : 0,
            'bio'                  => @$this->bio ?: '',
            'is_agent'             => $this->is_agent,
            'is_gold_id'           => $color_image ? true : false,
            'image_color'          => $color_image,
            'my_agency'            => $this->ownAgency()->select('id', 'name', 'notice', 'status', 'phone', 'url', 'img', 'contents')->first(),

            'online'               => $presence['online'],
            'last_seen_at'         => $presence['last_seen_at'],
            'online_time'          => UserDataHelper::formatOnlineTime($this->resource),

            'has_color_name'       => $this->getPackWithTypeV2(18),
            'anonymous'            => $this->getPackWithTypeV2(17),
            'country_hidden'       => $isHideCountry, // both
            'last_active_hidden'   => $this->getPackWithTypeV2(19),
            'visit_hidden'         => $this->getPackWithTypeV2(19),
            'room_hidden'          => $this->getPackWithTypeV2(16),
            'type_user'            => intval($this->type_user) ?: 0,
            "change_room_effect"   => new ShowUserSettingResource(@$show_user_setting),
            "chat_setting" => new ChatSettingResource($chat_setting),
            "manger_type"          => new MangerTypeResource(@$this->mangerType),
            "top_three_support"    => $userHandling->getTopThreeSupport($this->id),
            'level' => Common::level_center_v2($this),
            'profile_frame' => common::wareUserVipV2($this, 28, 'img2', isLatest: true),
            'profile_frame_id' => common::wareUserVipV2($this, 28, 'id', isLatest: true),
            'image_color'          => @$this->color_image,
            "multi_images" => $this->images?->select("img"),
            'user_types' => $this->user_types,
            "shipping-agency" => $this->shippingAgency ? [
                "id" => $this->shippingAgency->id,
                "name" => $this->shippingAgency->name ?? '',
                "image" => $this->shippingAgency->img ?? '',
                "complete-transactions" => $this->shippingAgency->charges?->count() ?? 0,
            ] : null,
        ];

        if (@$this->is_mic == '0' || @$this->is_mic == '1') {
            $data['is_mic'] = $this->is_mic;
        }
        if ($this->pivot) {
            $data['visit_time'] = $this->pivot->updated_at;
        }
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
