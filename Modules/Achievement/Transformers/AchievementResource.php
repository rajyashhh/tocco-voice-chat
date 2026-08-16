<?php

namespace Modules\Achievement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;

class AchievementResource extends JsonResource
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
            'type' => $this->type,
            'valid_image' => $this->valid_image,
            'invalid_image' => $this->invalid_image,
            'target' => $this->target,
            'target_type' => $this->target_type,
            // 'levels' => $this->whenLoaded('levels', function () {
            //     return $this->levels->map(function ($level) {
            //         $img= $level->invalid_image;
            //         if($level->enable == true){
            //             $img=$level->valid_image;
            //         }
            //         return [
            //             'id' => $level->id,
            //             'achievement_id' => $level->achievement_id,
            //             'gift_id' => $level->gift_id,
            //             'target' => $level->target,
            //             'target_type' => $level->target_type,
            //             'image' => $img,
            //             'created_at' => $level->created_at->toISOString(),
            //             'updated_at' => $level->updated_at->toISOString(),
            //             'deleted_at' => $level->deleted_at,
            //             'enable' => $level->enable,
            //         ];
            //     });
            // }),
        ];
    }
}
