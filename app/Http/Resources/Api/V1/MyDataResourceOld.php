<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\Pack;
use App\Models\Room;
use App\Models\User;
use App\Models\Family;
use App\Helpers\Common;
use App\Models\ImageColor;
use App\Helpers\UserCommon;
use App\Facades\UserHandling;
use App\Models\AgencyJoinRequest;
use Modules\FixedTarget\Entities\SpecialUser;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\MangerTypeResource;
use Modules\Public\Http\Services\UserCounterServices;
use App\Http\Resources\Api\V1\ShowUserSettingResource;
use App\Models\Agency;
use Modules\AgencyApp\Entities\AgencyUserJob;

class MyDataResourceOld extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        Pack::query()
            ->where('expire', '!=', 0)
            ->where('expire', '<', time())->delete();


        $agency_joined = $this->agency;
        if ($agency_joined != null) {
            //            $owner = $agency_joined->app_owner_id == $this->id ? new \stdClass() : new MiniUserResource($agency_joined->owner);
            if ($this->agency != null) {
                $agency_joined = [
                    'id'     => $this->agency->id,
                    'name'   => $this->agency->name,
                    'status' => $this->agency->status,
                    // 'owner'=>$owner,
                ];
            } else {
                $agency_joined = null;
            }
        }
        $pass_status = false;
        $now_room    = Room::query()->where('uid', $this->now_room_uid)->first();
        if ($now_room) {
            if ($now_room->room_pass) {
                $pass_status = true;
            }
        }

        $f      = null;
        $family = Family::query()->where('id', @$this->family_id)->first();
        if ($family) {
            $f = [
                'owner_id'    => $family->user_id,
                'family_name' => $family->name,
                'max_num'     => $family->num,
                //                'img'           =>$family->image,
                'members_num' => $family->members_count,
                //                'level'         =>$family->level
            ];
        }

        /*$packs = Pack::query()
            ->where ('user_id',$this->id)
            ->where (function ($q){
                $q->where('expire',0)->orWhere('expire','>=',now ()->timestamp);
            })
            ->where('is_used', 1)
            ->get()
            ->pluck('type')
            ->toArray();
        $previliges = [];
        $previliges['no_kick']            = in_array(9, $packs);
        $previliges['intro_animation']    = in_array(11, $packs);
        $previliges['vip_gifts']          = in_array(14, $packs);
        $previliges['no_pan']             = in_array(15, $packs);
        $previliges['anonymous_man']      = in_array(17, $packs);
        $previliges['colored_name']       = in_array(18, $packs);


//        $previliges = [
//            'no_kick'=>Common::pack_get (9,$this->id),
//            'intro_animation'=>Common::pack_get (11,$this->id),
//            'vip_gifts'=>Common::pack_get (14,$this->id),
//            'no_pan'=>Common::pack_get (15,$this->id),
//            'anonymous_man'=>Common::pack_get (17,$this->id),
//            'colored_name'=>Common::pack_get (18,$this->id),
//        ];*/

        $color_image = ImageColor::select("id", "image", "color")->find($this->image_color_id);

        $frame              =
            Common::getUserDress($this->id, $this->dress_1, 4, 'img2', true) ?: Common::getUserDress($this->id, $this->dress_1, 4, 'img1', true);
        $bubble             =
            Common::getUserDress($this->id, $this->dress_2, 5, 'show_img', true);
        $intro              =
            Common::getUserDress($this->id, $this->dress_3, 6, 'img2', true) ?: Common::getUserDress($this->id, $this->dress_3, 6, 'img1', true);
        $isHideCountry      = Common::hasInPack($this->id, 13, true);

        $show_user_setting = \App\Models\UserSetting::where("user_id", $this->id)->first();
        if ($show_user_setting == null) {
            $show_user_setting = \App\Models\UserSetting::create([
                'user_id'     => $this->id,
                'show_git'    => 1,
                'show_intro'  => 1,
                'show_banner' => 1,
            ]);
        }
        $types = ['system_message', 'official_message', 'followers', 'followeds', 'friend', 'visitor', 'mybag','mall'];
        $userCounterServices = new \Modules\Public\Http\Services\UserCounterServices();
        $user = User::find($this->id);

        $counters = collect($types)->mapWithKeys(function ($item) use ($userCounterServices, $user) {
            return [$item => $userCounterServices->getUserCounts($user, $item)];
        });

        $agency_joined = $this->agency;
        if ($agency_joined != null) {
            $owner = $agency_joined->app_owner_id == $this->id ? new \stdClass() : new MiniUserResource($agency_joined->owner);
            if ($this->agency != null) {
                $agency_joined = [
                    'id' => $this->agency->id,
                    'name' => $this->agency->name,
                    'status' => $this->agency->status,
                    'owner' => $owner,
                ];
            } else {
                $agency_joined = null;
            }
        }
        $owner = Agency::where('app_owner_id', $this->id)->first();
        $admin = AgencyUserJob::where('user_id', $this->id)->where('type', 'requestManger')->first();

        $data               = [
            'id'                   => @$this->id, // both
            'notification_id'      => @$this->notification_id ?: "", // both
            'name'                 => @$this->name ?: 'user' . ' ' . '#' . @$this->uuid, // both
            'unread_message_count' => $this->unread_count_message,
            'phone'                => (string)@$this->phone ?: '1', // both     stirng
            'frame'                => $frame, // both
            'intro'                => $intro, // both
            'bubble'               => $bubble, // both
            //            'is_gold_id'           => false,
            //            'image_color'          => $color_image,
            'bubble_id'            => @$bubble != '' ? $this->dress_2 : 0, // both
            'frame_id'             => $frame != '' ? @$this->dress_1 : 0, // both
            'intro_id'             => $intro != '' ? @$this->dress_3 : 0, // both
            'vip_target_id'        => $this->UserVip?->id ?? 0,
            'is_first'             => @(bool)$this->is_points_first, // my data
            'is_agency_request'    => (bool)AgencyJoinRequest::where('user_id', @$this->id)->where('status', '!=', 2)->count(),
            'game_Available'             => (bool)UserHandling::chickLevelToPlay($this->resource),
            // my
            'has_room'             => $this->hasRoom(), // my
            'facebook_bind'        => @$this->facebook_id ? true : false, // my
            'google_bind'          => @$this->google_id ? true : false, // my
            'phone_bind'           => @$this->phone ? true : false, // my
            'vip'                  => @Common::ovip_center($this), // both
            'family_id'            => @$this->family_id, // both
            'uuid'                 => @$this->uuid, // both
            'color'    => @$this->color_id ??'',
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,

            'bio'                  => @$this->bio ?: '', // both
            'number_of_fans'       => $this->followers_ids()->count(), // both
            'number_of_followings' => $this->followeds_ids()->count(), // both
            'number_of_friends'    => $this->numberOfFriends(), // both
            'profile_visitors'     => $this->profileVisits()->count(), // both
            'profile'              => new ProfileResource(@$this->profile), // both
            'level'                => Common::level_center(@$this), // both
            'my_store'             => [
                'id'           => $this->id,
                'coins'        => $this->di,
                'diamonds'     => $this->total_diamond_received,
                'silver_coins' => $this->gold,
                'usd'          => (float)$this->salary,
            ], // my
            'family_data'          => $f, // refactor
            'agency'               => @$agency_joined ?? new \stdClass(), // both
            'has_color_name'       => Common::hasInPack($this->id, 18, true), // both
            'anonymous'            => Common::hasInPack($this->id, 17, true), // both
            'room_hidden'          => Common::hasInPack($this->id, 16, true), // both
            'allow_upload_gif'                  => Common::hasInPack($this->id, 22, true), // both
            'country'              => ($this->country ?? ''),
            'chat_id'              => @$this->chat_id ?: "", // both
            'country_hidden'       => $isHideCountry, // both
            'is_special_user'      => SpecialUser::query()->where('user_id', $this->id)->where('status', true)->exists(),
            'view_invitation'      => UserCommon::CheckUserNew(@$this->id),
            "change_room_effect"   => new ShowUserSettingResource(@$show_user_setting),
            'type_user'            => intval(@$this->type_user) ?: 0, // both
            "manger_type"          => new MangerTypeResource(@$this->mangerType),
            'sound_effect_color'   => UserHandling::soundEffect($this->resource),
            'user_agency_status' => isset($owner) ? 2 : ($admin ? 1 : 3),

            $this->mergeWhen($request->show_counter == true, [
                'unread_counter'       =>  $counters,
            ]),

        ];
//        $data['auth_token'] = @$this->auth_token;
        if (@$this->is_mic == '0' || @$this->is_mic == '1') {
            $data['is_mic'] = $this->is_mic;
        }
        if ($this->pivot) {
            $data['visit_time'] = $this->pivot->updated_at;
        }
        return $data;
    }
}
