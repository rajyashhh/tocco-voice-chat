<?php

namespace Modules\Achievement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;

class AchievementDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $achievementLevel = $this->achievementLevel;
        return [
            'id' => $this->id,
            'name' => 'قام ' . $this->user?->name . ' بشحن قيمه' . $achievementLevel?->target ?? '',
            'type' => $achievementLevel?->achievement?->type ?? 'no achievement',
            'image' => $achievementLevel?->valid_image ?? ($this->custom_image ?? $this->file) ??$this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '',
            'description' =>    $achievementLevel ?  $achievementLevel?->ar_description  : __('get it by admin'),
            'description_en' =>    $achievementLevel ?   $achievementLevel?->en_description : __('get it by admin'),

        ];
    }
}
