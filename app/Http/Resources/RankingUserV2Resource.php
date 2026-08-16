<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use App\Http\Resources\Api\V1\MangerTypeResource;
use Illuminate\Pagination\LengthAwarePaginator;
class RankingUserV2Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  
     public function toArray(Request $request): array
     {
         $user = $this->resource['user'];
         $userExp = $this->resource['userExp'] ?? 0;
 
         $achievement_images = []; 
 
         return [
             'user_id'           => $user->id,
             'uuid'              => $user->uuid,
             'exp'               => $userExp,
             'name'              => $user->name,
             'avatar'            => $user->relationLoaded('profile') ? $user->profile?->avatar : null,
             'frame'             => $user->frame,
             'frame_id'          => $user->frame_id,
             'manger_type_id'    => $user->manger_type_id,
             'age'               => $user->relationLoaded('profile') ? ($user->profile?->age ?? null) : null,
             'vip_level'         => $user->relationLoaded('UserVip') ? ($user->UserVip?->level ?? 0) : 0,
             'sender_level'      => $user->total_sender_level ?? 0,
             'reciver_level'     => $user->total_received_level ?? 0,
             'vip_level_img'     => UserPackHelper::getVipIcon($user),
             'sender_level_img'  => UserLevelHelper::getSenderImage($user),
             'reciver_level_img' => UserLevelHelper::getReceiverImage($user),
             'type_user'         => intval(@$user->type_user) ?: 0,
             'country'           => $user->relationLoaded('country') ? $user->country : null,
             'manger_type'       => $user->relationLoaded('mangerType') ? new MangerTypeResource($user->mangerType) : null,
             'color_name'        => UserPackHelper::getColorName($user),
             'achievement_images'=> $achievement_images,
         ];
     }
    
    
    
}
