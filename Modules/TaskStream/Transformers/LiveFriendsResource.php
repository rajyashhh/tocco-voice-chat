<?php

namespace Modules\TaskStream\Transformers;

use App\Helpers\UserPackHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveFriendsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id' => $this->id,
            'avatar' => $this->profile?->avatar,
            'level' => [
                'sender_img' => $this->senderLevel?->img  ?? '',
                'receiver_img' => $this->receiverLevel?->img ?? '',
            ],
            'vip' =>  [
                'vip_icon' => UserPackHelper::getVipIcon($this->resource),
                'level' => $this->UserVip?->level,
            ],
            'room' => $this->ownerRoom ? [
                'owner_uuid' => $this->uuid ?? '',
                'owner_name' => $this->name ?? '',
                'room_name' => $this->ownerRoom->room_name ?? '',
                'cover' => $this->ownerRoom->room_cover ?? '',
                'room_id' => $this->ownerRoom->id ?? null,
                'room_intro' => $this->ownerRoom->room_intro ?? '',
                'room_type' => $this->ownerRoom->type ?? '',
            ] : null,
        ];
    }
}
