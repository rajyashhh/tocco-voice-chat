<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\ShippingAgency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ChargeResourceforAgencyCharge extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $sender = Common::getChargerInfo($this);
        $is_sender = ShippingAgency::where('id', $sender['id'])
        ->where('app_owner_id', Auth::user()->id)
        ->exists();

        return [
            'id'   => $this->id ?: 0,
            'sender' => $sender,
            'receiver' =>  Common::getReceiverInfo($this),
            'value' => (int) $this->amount,
            'time' => ($this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d h:i:s A') : null),
            'coins' =>  (int)$this->amount ?? 0,
            'usd' => $this->usd ?? 0,
            'is_sender' => $is_sender ?? 0,
        ];
    }
}
