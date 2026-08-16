<?php

namespace Modules\SalaryTransaction\Transformers;

use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievementLevel;

class HostRequestsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'host_id' => $this->agency?->owner?->id,
            'host_name' =>$this->agency?->owner?->name,
            'host_uuid' => $this->agency?->owner?->uuid,
            'host_image' => $this->agency?->owner?->profile?->avatar,
            'status' => $this->status,
            'payment_gateway' => $this->payment_gateway?->title,
            'payment_gateway_image' => $this->payment_gateway?->photo,
            'bill_image' => $this->bill_image,
            'country_name' => $this->country?->name,
            'country_flag' => $this->country?->flag,
            'usd' => $this->usd,
            'coins' => $this->coins,  
            'note' => $this->note,
            'has_color_name' => Common::hasInPack(@$this->agency?->owner?->id ?? 0, 18),
            'country_hidden'       => Common::hasInPack(@$this->agency?->owner?->id, 13, true),
        ];
    }
}
