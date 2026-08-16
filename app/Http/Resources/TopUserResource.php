<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use App\Http\Resources\Api\V1\MangerTypeResource;
use Illuminate\Pagination\LengthAwarePaginator;

class TopUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'role'              => $this->role ?? null,
            'ranker_id'         => $this->ranker_id,
            'ranker_type'       => $this->ranker_type,
            'total_gifts'       => $this->total_gifts,
            'last_calculated_at' => $this->last_calculated_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'exp_diff'          => $this->exp_diff ?? 0,
            'user_id'           => $this->user_id,
            'color_name'        => $this->color_name ?? null,
            'exp'               => $this->exp ?? null,
            'exp_int'           => $this->exp_int ?? null,
            'remaining'         => $this->remaining ?? '0',
            'remaining_int'     => $this->remaining_int ?? 0,
            'name'              => request('class') == 3 ? $this->ranker?->ownerRoom?->room_name : $this->ranker?->name,
            'avatar'            => request('class') == 3 ? $this->ranker?->ownerRoom?->room_cover : $this->ranker?->profile?->avatar ?? '',
            'frame'             => $this->frame,
            'frame_id'          => $this->frame_id,
            'type_user'         => $this->type_user,
            'manger_type'       => $this->manger_type,
            'vip_level'         => $this->vip_level,
            'sender_level'      => $this->sender_level,
            'reciver_level'     => $this->reciver_level,
            'vip_level_img'     => $this->vip_level_img,
            'sender_level_img'  => $this->sender_level_img,
            'reciver_level_img' => $this->reciver_level_img,
            'country'           => $this->country ?? null,
            'age'               => $this->age ?? null,
            'achievement_images' => $this->achievement_images ?? [],
            'room'              => null,
            'ranker'            =>  null,
            'packs'             =>  [],
            'user_vip'          => null,
            'profile'           => $this->ranker?->profile ?? null,
        ];
    }
}
