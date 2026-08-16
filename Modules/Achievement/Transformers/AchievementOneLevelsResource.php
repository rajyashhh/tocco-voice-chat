<?php

namespace Modules\Achievement\Transformers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievementLevel;

class AchievementOneLevelsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {

        $targets = [
            'recharge_target' => 'recharge-target',
            'room_target' => 'room-target',
        ];
        
        $target = $targets[$this->type->value] ?? 'gift-target';
        return [
            'id' => $this->id,
            'type' => $this->type,
            'valid_image' => $this->valid_image,
            'invalid_image' => $this->invalid_image,
            'target' => $this->target,
            'target_type' => $this->target_type,
            'levels' => $this->whenLoaded('levels', function ()use($target) {
                return $this->levels->map(function ($level) use($target) {
                    $img = $level->invalid_image;
                   
                        $valid = $level->valid_image;
                    $description_en = $level->en_description;
                    $description = $level->ar_description;
                    return [
                        'id' => $level->id,
                        'achievement_id' => $level->achievement_id,
                        'gift_id' => $level->gift_id,
                        'name' => $target . '-'. $level->target,
                        'target' => $level->target,
                        'target_type' => $level->target_type,
                        'image' => $img,
                        'valid_image'=> $valid,
                        'created_at' => Carbon::parse(@$level?->created_at)->toISOString(),

                        'updated_at' => Carbon::parse(@$level?->updated_at)->toISOString(),
                        'deleted_at' => $level->deleted_at,
                        'enable' => $level->enable,
                        'description' => $description,
                        'description_en' => $description_en,

                    ];
                });
            }),
        ];
    }
}
