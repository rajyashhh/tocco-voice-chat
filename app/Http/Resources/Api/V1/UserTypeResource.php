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

class UserTypeResource extends JsonResource
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
        $now_room = $this->room;

        if ($now_room) {
            if ($now_room->room_pass) {
                $pass_status = true;
            }
        }

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
            'uuid'       => @$this->uuid,
            'name'       => @$this->name ?: '',
            'profile'    => [
                'image'  => $this->profile?->avatar,
                'age'    => Carbon::parse($this->profile?->birthday)->age,
                'gender' => $this->profile?->gender ?? 1,
                'country'=> $this->profile?->country?:''
            ],
            'frame'          => $frameAbility ? (@$this->ware->img2 ?: @$this->ware->img1) : '',
            'frame_id'   => @$this->dress_1,
            
            'vip'        => [
                'level' => @$this->UserVip->level,
            ],
            'level'      => [
                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
                'sender_img'   => $imageSender ? @$imageSender->img : '',
                'sender_level'  =>@$this->total_sender_level ?? 0,
                'receiver_level' =>@$this->total_received_level ?? 0
            ],

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
