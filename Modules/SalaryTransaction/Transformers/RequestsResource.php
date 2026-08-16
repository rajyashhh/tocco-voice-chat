<?php

namespace Modules\SalaryTransaction\Transformers;

use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievementLevel;

class RequestsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'host_id' => $this->host?->id,
            'host_name' => $this->host?->name,
            'host_uuid' => $this->host?->uuid,
            'host_image' => $this->host?->profile?->avatar,
            'status' => $this->status,
            'payment_gateway' => $this->payment_gateway?->title,
            'payment_gateway_image' => $this->payment_gateway?->photo,
            'country_name' => $this->country?->name,
            'country_flag' => $this->country?->flag,
            'usd' => $this->usd,
            'coins' => $this->coins,
            'note' => $this->note,
            'has_color_name' => Common::hasInPack(@$this->host?->id ?? 0, 18),
            'country_hidden'       => Common::hasInPack(@$this->host?->id, 13, true), // both
        ];
    }
}
