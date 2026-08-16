<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Pack;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class MyPacksVipResource extends JsonResource
{
    public function toArray($request)
    {

        /** @var Pack $this */
        $expiration_in_seconds = ($this->expire - time());
        $expiration_in_days = floor($expiration_in_seconds / (24 * 60 * 60));
        return [
            'id' => $this->id,
            'target_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => "vip bag",
            'num' => $this->qty,
            'expire' => $expiration_in_days,
            'created_at' => Carbon::parse($this->created_at)->setTimezone($request->hasHeader('tz') ? $request->header()['tz'][0] : 'UTC')->format('Y-m-d H:i:s') ??'',
            'updated_at' => $this->updated_at,
            'sender_id' => $this->sender_id,
            'use' => $this->is_used == 1? true : false,
            'is_used' => $this->num_used > 0,
            'num_used' => $this->num_used > 0,
            'name' => $this->OVip?->name,
            'show_img' => $this->OVip?->img,
            'price' => @$this->OVip?->price ?? '',
        ];
    }


}
