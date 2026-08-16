<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;

use App\Models\configesModel;
use App\Models\Pk;
use App\Models\RequestBackgroundImage;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\CP\Entities\CpRoomHistory;

class EnterRoomLiveCollection extends JsonResource
{


    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

      

        request()->type = 1;
        $owner = $this->owner;


     

        return [
            "id"                  => $this->id, // room id
            "owner_id"            => $this->uid, // owner id
            "uuid"                => $owner?->uuid ?? '', // owner uuid
            "room_name"           => $this->room_name, // room name
            "room_intro"          => $this->room_intro, // room intro
            'owner_image_color'   => @$owner?->color_image,
            "owner_name"          => @$owner->name ?? '', // owner name
            "owner_image"         => $owner->avatar ?: '', // owner image
            "giftPrice"           => $this->session_string ?: '', // gift price
            "password_status"     => !($this->room_pass == ""), // room password state
            "room_pass"           => $this->room_pass,
            "admins"              => explode(',', $this->room_admin ?? ''), // room admins
            "is_comment_closed"   => $this->is_comment_closed, // is Comments Closed
            "room_rule"           => Common::getConfig('room_rule' . (app()->getLocale() != 'ar' ? '_en' : '')), // room rules
            'is_live'             => (bool) ($this->is_live ?? false),
            "room_welcome"        => $this->room_welcome,
            'owner_special_id'    => @$owner?->specialId?->ware?->show_img ?? "",
            'vip' => [
                'id'        => 1,
                'level'     =>  0,
                'name'      =>  '',
                'price'     => 0,
                'img_old'   =>  '',
                'img'       =>  '',
                'image'     => '',
                'image_from_wares'     => '',
                'expire'    =>  0,
                'ware_id'   =>  0,
                'color'     =>  '',
                'vip_gifts' => 0,
                'vip_upload_gif' => 0,
                'colored_name' =>  '',
            ],

        ];
    }




}
