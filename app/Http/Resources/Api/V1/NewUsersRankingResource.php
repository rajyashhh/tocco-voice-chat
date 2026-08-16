<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use Illuminate\Http\Resources\Json\JsonResource;


class NewUsersRankingResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->ranker;

        if (!$user) {
            return null;
        }

        // Collect achievement images
        $achievement_images = [];
        if ($user->medals) {
            foreach ($user->medals as $medal) {
                $achievement_images[] = [
                    'image' => $medal->custom_image ?? @$medal->achievementLevel->valid_image,
                    'title' => @$medal->achievementLevel?->achievement?->name ?? 'Reward',
                    'created_at' => $medal->created_at,
                ];
            }
        }
        $hasColor = Common::hasInPackV2($user->packs, 18, true);

        $color_name = (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVipV2($this->user_id, 18, 'color') : null);

        $value = $this->total_gifts;
        $expInt = ceil((float) $value);

        return [
            'user_id'          => $user->id,
            'uuid'             => $user->uuid,
            'exp'              => numToString($expInt),
            'exp_int'          => $expInt,
            'remaining'        => numToString($this->exp_diff ?? 0),
            'remaining_int'    => ceil($this->exp_diff ?? 0),
            'name'             => $this->class == 3 ? ($user->ownerRoom?->room_name ?? '') : $user->name,
            'avatar'           => $this->class == 3 ? ($user->ownerRoom?->room_cover ?? '') : ($user->profile?->avatar ?? ''),
            'frame'            => UserPackHelper::getFrameImage($user),
            'frame_id'         => UserPackHelper::getFrameId($user),
            'type_user'        => intval($user->type_user) ?: 0,
            'manger_type'      => !$user->mangerType ? null : new MangerTypeResource($user->mangerType),
            'vip_level'        => $user->UserVip?->level ?? 0,
            'sender_level'     => $user->total_sender_level ?? 0,
            'reciver_level'    => $user->total_received_level ?? 0,
            'vip_level_img'    => $user->UserVip?->OVip?->img ?? '',
            'sender_level_img' => UserLevelHelper::getSenderImage($user),
            'reciver_level_img'=> UserLevelHelper::getReceiverImage($user),
            'country'          => $user->country,
            'age'              => $user->profile?->age ?? '',
            'achievement_images' => $achievement_images,
            'color_name'       => $color_name,
            'room'             => $this->class == 3 ? $this->roomData($user->ownerRoom) : null,
        ];
    }
}
