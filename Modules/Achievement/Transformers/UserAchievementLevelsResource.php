<?php

namespace Modules\Achievement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Entities\AchievementLevel;

class UserAchievementLevelsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $description = app()->getLocale() === 'ar' ? $this->ar_description : $this->en_description;
        $title = __('this achievement is taken from Admin');
        return [
            'id' => $this->id,
            'image' => $this->valid_image ?? $this->custom_image ?? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '',
            'description' => $this->custom_image ? ($title ?? '') : ($description ?? ''),
            'type'  => $this->type == "room_target" ? 2 : 1,
        ];
    }
}
