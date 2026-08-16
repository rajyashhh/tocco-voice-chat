<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\User;
use App\Helpers\Common;
use App\Facades\UserHandling;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Http\Services\UserAchievementService;

class ShowUserResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'coins' => number_format($this->di),
            'uuid' => $this->uuid,
            'original_uuid' =>  $this->original_uuid,
            'name' => $this->name ?? '',
            'nickname' => $this->nickname ?? '',
            'charge_status' => $this->charge_status,
            'transfer_salary' => $this->transfer_salary,
            'hide_chat' => @$this->userSetting->hide_chat,
            'show_invite_code' => @$this->userSetting->show_invite_code,
            'country_id' => @$this->country_id ?? 0,
            'user_diamond' => $this->user_diamond,
            'total_sender_level' => $this->sender_level,
            'total_received_level' => $this->received_level,
            'gender' => $this->profile?->gender,
            'avatar' => $this->profile?->avatar ?? '',
            'image_id' => $this->profile?->image_id ?? '',
            'can_play' => UserHandling::chickLevelToPlay($this->resource),
            'phone' => $this->phone ?? '',
            'email' => @$this->email ?? '',
            'facebook_id' => @$this->facebook_id,
            'google_id' => @$this->google_id,
            'huawei_id' => @$this->huawei_id,
            'status' => @$this->status,
            'type_user' => $this->type_user,
            'manger_type_id' => $this->manger_type_id ?? 0,
            'Level' =>  Common::level_center($this->resource)['sender_level'],
            'worth' =>  Common::level_center($this->resource)['receiver_level'],
            'diamonds' =>  $this->coins,
            'balance' =>  $this->salary,
            'pack' => PackUserResource::collection($this->packs),
            'vip' => VipUserResource::collection($this->haveVip)


        ];
    }
}
