<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineResource extends JsonResource
{

    public function toArray($request)
    {
        $chatRoom = $this->chatRoomsAsUser->first() ?? $this->chatRoomsAsUser2->first();

        return [
            'id' => $this->id ?? 0,
            'uuid' => $this->uuid ?? 0,
            'name' => $this->name ?? '',
            'image' => $this->profile?->avatar ?? '',
            'country' => @$this->country,
            'is_followed'            => $this->is_followed,
            'is_follow'            => $this->is_follow, // user data  ----
            'is_friend'            => $this->isFriends(),
            'chat_id' => $chatRoom->id ?? null,
            'deleted_at' => $this->deleted_at ?? '',
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
        ];
    }
}
