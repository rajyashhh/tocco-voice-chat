<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Http\Resources\CountryResource;
use App\Models\Follow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRelationsResource extends JsonResource
{
    public static $vipsReceivedImages = null;
    public static $vipsSenderImages = null;
    public static $userFollowers = null;

    public function __construct($resource) {  parent::__construct($resource); }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $pass_status = false;
//        $isHideCountry = $this->getPackWithTypeV2(13);

//        $now_room = @$this->room;
//
//        if ($now_room) {
//            if ($now_room->room_pass != null && $now_room->room_pass != '') {
//                $pass_status = true;
//            }
//        }

        if(self::$userFollowers != null && count(self::$userFollowers) > 0){
            $isFollow = in_array(@$this->id, self::$userFollowers);
        }else{
            $isFollow = @(bool)Common::IsFollow(@$request->user()->id, @$this->id);
        }

        if (!self::$vipsReceivedImages && !self::$vipsSenderImages) {
            $imageReceiver = @$this->getImageReceiverOrSender('receiver_id', 1);
            $imageSender   = @$this->getImageReceiverOrSender('sender_id', 2);
        } else {
            $imageReceiver = count(self::$vipsReceivedImages) > 0 ? self::$vipsReceivedImages->where('level', @$this->total_received_level)->first() : null;
            $imageSender = count(self::$vipsSenderImages) > 0 ? self::$vipsSenderImages->where('level', @$this->total_sender_level)->first() : null;
        }
        $frameAbility = @$this->followPacks->where('type', 4)->first();

        if ($this->relationLoaded('chatRoomsAsUser') || $this->relationLoaded('chatRoomsAsUser2')) {
            $chatRoom = $this->chatRoomsAsUser->first() ?? $this->chatRoomsAsUser2->first();
        }

        $data         = [
            'id'             => @$this->id,
            'name'           => @$this->name ?: '',
            'profile'        => [
                'image'  => @$this->profile->avatar,
            ],
            'frame'          => $frameAbility ? (@$this->ware->img2 ?: @$this->ware->img1) : '',
            'frame_id'       => @$this->dress_1,
            'vip'            => [
                'level' => @$this->UserVip->level,
                 'img' => Common::ovip_center_rank_img_v2($this),
            ],
            'level'          => [
                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
                'sender_img'   => $imageSender ? @$imageSender->img : '',
                'sender_level'  =>@$this->total_sender_level ?? 0,
                'reciver_level' =>@$this->total_received_level ?? 0
            ],
            'is_followed'          => $this->followedByAuthUser !== null,
            'is_follow'            => $this->followerByAuthUser !== null,
            'image_color'          => @$this->color_image,
            'color_name'   => (fn($c) => is_string($c) ? $c : '')(common::wareUserVipColorV2($this, 18)),
            'chat_id' => $chatRoom->id ?? null,
            'deleted_at' => $this->deleted_at,
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
        ];

        return $data;
    }

    public function additional(array $data)
    {
        return parent::additional($data);
    }

//    /**
//     * @return array
//     */
//    public function getAdditional(): array
//    {
//
//        return $this->additional;
//    }
//
//    /**
//     * @param array $additional
//     */
//    public function setAdditional(array $additional): void
//    {
//
//        $this->additional = $additional;
//    }

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
