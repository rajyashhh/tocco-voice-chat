<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomVisitorsResource extends JsonResource
{

    public static  $vipsReceivedImages = null;
    public static $vipsSenderImages = null;
    public static int|null $roomOwnerId = null;
    public static array $roomAdmins = [];


    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (!self::$vipsReceivedImages && !self::$vipsSenderImages) {
            $imageReceiver = $this->getImageReceiverOrSender('receiver_id', 1);
            $imageSender   = $this->getImageReceiverOrSender('sender_id', 2);
        } else {

            $imageReceiver = self::$vipsReceivedImages->count() > 0 ? self::$vipsReceivedImages->where('level', $this->total_received_level)->first() : null;
            $imageSender = self::$vipsReceivedImages->count() > 0 ? self::$vipsSenderImages->where('level', $this->total_sender_level)->first() : null;
        }

        $frameDress  = @$this->dress1;

        $frame  =
            (@$this->packs?->where('type', 4)->first() != null)? (($frameDress != null) ? $frameDress->img2: ''): '';


        return [
            'id'=>@$this->id, // both //
            'uuid'=>@$this->uuid, // both //
            'name'=>@$this->name?:'', // both //
            'uuid'                 => @$this->uuid, // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,

            'profile_image'=>@$this->profile->avatar ?? '', // both //
            'level'      => [
                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
                'sender_img'   => $imageSender ? @$imageSender->img : '',
                'sender_level'  =>@$this->total_sender_level ?? 0,
                'reciver_level' =>@$this->total_received_level ?? 0
            ],

            'vip'        => [
                'level' => @$this->UserVip->level,
            ],
            'bubble'=> @$this->packs->where('type', 5)->first() != null,// both
            'frame'=> $frame, // both
            'frame_id'=>$frame != ''? @$this->dress_1 : 0, // both
            'has_color_name'=>@$this->packs?->where('type', 18)->first() != null, // both
            'type' => (self::$roomOwnerId != null && @$this->id == self::$roomOwnerId) ? 0 : (gettype(self::$roomAdmins) == 'array' && array_search(@$this->id, self::$roomAdmins) !== false ? 1 : 2),
            "manger_type"          =>new MangerTypeResource(@$this->mangerType),
            'type_user' => @$this->type_user ?? 0

        ];


    }

    public static function initializeData($vipsSenderImages, $vipsReceivedImages, array $roomAdmins, int $roomOwnerId)
    {
        self::$vipsSenderImages = $vipsSenderImages;
        self::$vipsReceivedImages = $vipsReceivedImages;
        self::$roomAdmins = $roomAdmins;
        self::$roomOwnerId = $roomOwnerId;

    }

    public static function clear()
    {
        self::$vipsSenderImages = null;
        self::$vipsReceivedImages = null;
        self::$roomAdmins = [];
        self::$roomOwnerId = null;
    }
}
