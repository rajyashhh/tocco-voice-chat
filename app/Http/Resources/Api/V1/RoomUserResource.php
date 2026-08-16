<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use JsonSerializable;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomUserResource extends JsonResource
{

    public static $vipsReceivedImages = null;
    public static $vipsSenderImages = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {

        if (!self::$vipsReceivedImages && !self::$vipsSenderImages) {
            $imageReceiver = $this->getImageReceiverOrSender('receiver_id', 1);
            $imageSender   = $this->getImageReceiverOrSender('sender_id', 2);
        } else {
            $imageReceiver =
                count(self::$vipsReceivedImages) > 0 ? self::$vipsReceivedImages->where('level', $this->total_received_level)->first() : null;
            $imageSender   =
                count(self::$vipsSenderImages) > 0 ? self::$vipsSenderImages->where('level', $this->total_sender_level)->first() : null;
        }


        $bubbleDress = $this->dress2;

        $bubble =
            ($this->packs->where('type', 5)->first() != null) ? (($bubbleDress != null) ? $bubbleDress->show_img : '') : '';


        return [
            'id'   => @$this->id, // both //
            'uuid' => @$this->uuid, // both //
            'name' => @$this->name ?: '', // both //

            'profile_image' => @$this->profile->avatar ?? '', // both //
            'uuid'                 => @$this->uuid, // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'level'         => [
                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
                'sender_img'   => $imageSender ? @$imageSender->img : '',
                'sender_level'  =>@$this->total_sender_level ?? 0,
                'reciver_level' =>@$this->total_received_level ?? 0
            ],

            'vip'            => [
                'level' => @$this->UserVip->level,
            ],
            'bubble'         => @$bubble,// both
            'bubble_id'      => @$bubble != '' ? $this->dress_2 : 0, // both
            //'has_color_name' => @$this->packs->where('type', 18)->first() != null, // both
            // Use the packs already eager-loaded in usersRoom/usersRoomVisitor
            // (whereIn type [5,18]) instead of firing a fresh per-visitor query.
            // hasInPackV2 applies the same type=18 + active-expire + is_used=1
            // filter against the preloaded set, so the boolean is identical.
            'has_color_name'       => Common::hasInPackV2($this->packs, 18, true), // both
            "manger_type"          =>new MangerTypeResource(@$this->mangerType),
            'type_user' => @$this->type_user ?? 0

        ];

        //        if (!self::$vipsReceivedImages && !self::$vipsSenderImages) {
        //            $imageReceiver = $this->getImageReceiverOrSender('receiver_id', 1);
        //            $imageSender   = $this->getImageReceiverOrSender('sender_id', 2);
        //        } else {
        //            $imageReceiver = count(self::$vipsReceivedImages) > 0 ? self::$vipsReceivedImages->where('level', $this->total_received_level)->first() : null;
        //            $imageSender = count(self::$vipsSenderImages) > 0 ? self::$vipsSenderImages->where('level', $this->total_sender_level)->first() : null;
        //        }
        //
        //
        //
        //
        //        $frameDress  = $this->dress1;
        //        $bubbleDress  = $this->dress2;
        //        $introDress  = $this->dress3;
        //        $frame  =
        //            ($this->packs->where('type', 4)->first() != null)? (($frameDress != null) ? $frameDress->img2: ''): '';
        //        $bubble =
        //            ($this->packs->where('type',5)->first() != null)? (($bubbleDress != null) ? $bubbleDress->img2: ''): '';
        //        $intro  =
        //            ($this->packs->where('type',6)->first() != null)? (($introDress != null) ? $introDress->img2: ''): '';
        //
        //
        //        $f = null;
        //        $family = $this->family;
        //        if ($family){
        //            $f = [
        //                'owner_id'      => $family->user_id,
        //                'family_name'   =>$family->name,
        //                'max_num'       =>$family->num,
        //                'img'           =>$family->image,
        //                'members_num'   =>$family->members_count,
        //                'level'         =>$family->level
        //            ];
        //        }
        //
        //        $isCountry    = @$this->packs->where('type', 13)->first() != null;
        //        $data = [
        //            /**/'id'=>@$this->id, // both //
        //            /**/'uuid'=>@$this->uuid, // both //
        //            'chat_id'=>@$this->chat_id?:"", // both
        //            /**/'name'=>@$this->name?:'', // both //
        //
        //            'is_follow'=>(bool) $this->followeds_exists ?? false, // user data //
        //            'is_friend'=>((bool) $this->followeds_exists ?? false) || ($this->followers_exists ?? false), //
        //
        //            'agency'=>$this->agency ? [
        //                    'id'=>$this->agency->id,
        //                    'name'=>$this->agency->name,
        //        ] : new \stdClass(), // both
        //            'family_id'=>@$this->family_id, // both
        //            'family_data'=>@$f, // refactor //
        //
        //            /*profile img*/'profile'=>new ProfileResource(@$this->profile), // both //
        //           /**/ 'level'      => [
        //                'receiver_img' => $imageReceiver ? @$imageReceiver->img : '',
        //                'sender_img'   => $imageSender ? @$imageSender->img : '',
        //            ],
        //
        //           /**/ 'vip'        => [
        //                'level' => @$this->UserVip->level,
        //            ],
        //            /**/'frame'=> $frame, // both
        //            'intro'=> $intro,// both
        //            'bubble'=> $intro,// both
        //            'bubble_id'=>@$bubble != ''? $this->dress_2 : 0, // both
        //            /**/'frame_id'=>$frame != ''? @$this->dress_1 : 0, // both
        //            'intro_id'=>$intro != '' ?@$this->dress_3 : 0, // both
        //
        //            'bio'=>@$this->bio?:'', // both
        //            'is_agent'=>$this->is_agent, // both
        //            'my_agency'=>$this->ownAgency,
        //
        //            /**/'has_color_name'=>@$this->packs->where('type', 18)->first() != null, // both
        //            'country'=> $isCountry ?($this->country?:''):'', // both
        //            'country_hidden'=>$isCountry, // both
        //            'last_active_hidden'=>@$this->packs->where('type', 20)->first() != null, // both
        //        ];
        //
        //
        //        return $data;
    }

    public static function initializeData($vipsReceivedImages, $vipsSenderImages)
    {
        self::$vipsSenderImages   = $vipsSenderImages;
        self::$vipsReceivedImages = $vipsReceivedImages;

    }

    public static function clear()
    {
        self::$vipsSenderImages   = null;
        self::$vipsReceivedImages = null;
    }
}
