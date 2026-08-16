<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class NewUserRankingResource extends JsonResource
{
    public function toArray($request)
    {
        $ranker = $this->ranker;

        if (!$ranker) {
            return null;
        }

        $hasColor = Common::hasInPackV2($ranker->packs, 18, true);

        $color_name = (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVipV2($this->user_id, 18, 'color') : null);
        $achievement_images = [];
        if ($ranker->medals) {
            foreach ($ranker->medals as $medal) {
                if ($medal->achievementLevel) {
                    $achievementData = [
                        'image' => @$medal->achievementLevel->valid_image,
                        'title' => $medal->achievementLevel?->achievement?->name ?? '',
                        'created_at' => $medal->created_at,
                    ];
                    $achievement_images[] = $achievementData;
                }
            }
        }
        return [
            'user_id'          => $this->user_id,
            'uuid'             => $ranker->uuid ?? '',
            'exp'              => $this->exp,
            'exp_int'          => $this->total_gifts,
            'remaining'        => $this->remaining,
            'remaining_int'    => $this->remaining_int,
            'name'             => $this->name,
            'avatar'           => $ranker->profile?->avatar ?? '',
            'frame'            => $ranker->frame,
            'frame_id'         => $ranker->frame_id,
            'vip_level'        => $this->vip_level,
            'sender_img' => UserLevelHelper::getSenderImage($ranker),
            'sender_level'     => $this->sender_level,
            'reciver_level'    => $this->reciver_level,
            'vip_level_img'    => UserPackHelper::getVipIcon($ranker),
            'sender_level_img' => UserLevelHelper::getSenderImage($ranker),
            'reciver_level_img' => UserLevelHelper::getReceiverImage($ranker),
            'country'          => $this->country,
            'age'              => $ranker->profile?->age ?? '',
            'type_user'        => $this->type_user,
            'manger_type'        => $ranker->mangerType
                ? new MangerTypeResource($ranker->mangerType)
                : null,
            'achievement_images' =>  $achievement_images,
            'color_name'       => $color_name,
            'room'             => $ranker->room,
        ];
    }
}
