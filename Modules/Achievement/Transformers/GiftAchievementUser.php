<?php

namespace Modules\Achievement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;

class GiftAchievementUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'achievement_name' => $this->Achievement?->type ??'',
            'gift_name' => $this->gift?->name ?? '',
            'user_name' => $this->user?->name ?? '',
        ];
    }
}
