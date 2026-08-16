<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\Pack;
use App\Models\User;
use App\Models\Charge;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
//        if (!$this instanceof User) return [];
        $frame   =  Common::getUserDress($this->id, $this->dress_1, 4, 'img2', true) ?: Common::getUserDress($this->id, $this->dress_1, 4, 'img1', true);
        $charge = Charge::whereRaw('charger_id != user_id')->where(fn($q) => $q->where('charger_id',$this->id))->orWhere('user_id',$this->id )->count();
        $packSpecial = Pack::query()->where(function ($query) {
            $query->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->where('user_id', $this->id)->where("type", 25)->where('is_used', 1)->first();
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'phone' => $this->phone,
            'image' => @$this->profile->avatar?:'',
            'uuid'  => $this->uuid,
            'payment_getaway' => $this->paymentGateways?? [],
            'frame'                => $frame,
            'frame_id'             => $frame != '' ? @$this->dress_1 : 0, // both
            'level'                => $this->total_sender_level, // both
            'vip'                  => @$this->UserVip->level, // both
            'charge_count'     => $charge ?? 0,
            'id_image'             => @$packSpecial->ware->show_img ?? '',
            'special_id'          =>  @$packSpecial->ware->id ?? '',
        ];
    }
}
