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

class UserVisitorResource extends JsonResource
{
    public static $vipsReceivedImages = null;
    public static $vipsSenderImages = null;
    public static $userFollowers = null;
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pass_status = false;
//        $now_room = $this->room;
//
//        if ($now_room) {
//            if ($now_room->room_pass) {
//                $pass_status = true;
//            }
//        }

//        if (self::$userFollowers != null && count(self::$userFollowers) > 0) {
//            $isFollow = in_array($this->id, self::$userFollowers);
//        } else {
//            $isFollow = @(bool)Common::IsFollow(@$request->user()->id, $this->id);
//        }
        if (!self::$vipsReceivedImages && !self::$vipsSenderImages) {
            $imageReceiver = $this->getImageReceiverOrSender('receiver_id', 1);
            $imageSender   = $this->getImageReceiverOrSender('sender_id', 2);
        } else {
            $imageReceiver = count(self::$vipsReceivedImages) > 0 ? self::$vipsReceivedImages->where('level', $this->total_received_level)->first() : null;
            $imageSender = count(self::$vipsSenderImages) > 0 ? self::$vipsSenderImages->where('level', $this->total_sender_level)->first() : null;
        }
        $frameAbility = $this->followPacks->where('type', 4)->first();

        $data = [
            'id'         => @$this->id,
//            'uuid'       => @$this->uuid,
            'name'       => @$this->name ?: '',
//            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
//            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'visit_time' => Carbon::parse(@$this->pivot->updated_at)->setTimezone($request->hasHeader('tz') ? $request->header()['tz'][0] : 'UTC')->format('Y-m-d H:i:s'),
            'profile'    => [
                'image'  => @$this->profile->avatar,
//                'age'    => Carbon::parse(@$this->profile->birthday)->age,
//                'gender' => @$this->profile->gender ?? 1,
//                'country' => @$this->country ?: ''
            ],
            'frame'          => $frameAbility ? (@$this->ware->img2 ?: @$this->ware->img1) : '',
            'frame_id'   => @$this->dress_1,
//            'now_room'   => [
//                'is_in_room'      => @$this->now_room_uid != 0,
//                'uid'             => @$this->now_room_uid,
//                'is_mine'         => @$this->id == $this->now_room_uid,
//                'password_status' => $pass_status
//            ],
            'vip'        => [
                'level' => @$this->UserVip->level,
                'img' => Common::ovip_center_rank_img_v2($this),
            ],
            'level'      => [
                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
                'sender_img'   => $imageSender ? @$imageSender->img : '',
                'sender_level'  => @$this->total_sender_level ?? 0,
                'reciver_level' => @$this->total_received_level ?? 0
            ],
            'is_follow'            => $this->followerByAuthUser !== null,
//            'is_follow'      => $isFollow,
//            "manger_type"          => new MangerTypeResource(@$this->mangerType),
            'type_user' => @$this->type_user ?? 0,
            'image_color'          => @$this->color_image,
//            "statistic"     => [
//                "visitors" => count(@$this->profileVisits),
//                "licked" => count(@$this->likes),
//                "followers" => count(@$this->followers),
//                "bio" => @$this->bio,
//            ],
            'color_name'   => (fn($c) => is_string($c) ? $c : '')(common::wareUserVipColorV2($this, 18)),

        ];

        return $data;
    }

    public static function initializeData($vipsReceivedImages, $vipsSenderImages, $userFollowers)
    {
        self::$vipsSenderImages = $vipsSenderImages;
        self::$vipsReceivedImages = $vipsReceivedImages;
        self::$userFollowers = $userFollowers;
    }

    public static function clear()
    {
        self::$vipsSenderImages = null;
        self::$vipsReceivedImages = null;
        self::$userFollowers = null;
    }
}
