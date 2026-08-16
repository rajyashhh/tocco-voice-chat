<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner' => [
                'id' => $this->owner->id ?? 0,
                'name' => $this->owner->name ?? '',
                'uuid' => $this->owner->uuid ?? 0,
                'image' => $this->owner->profile?->avatar ?? '',
            ],
            'room_status' => $this->room_status,
            'top_room' => $this->top_room,
            'pin' => $this->pin,
            'sort_num' => $this->sort_num,
            'max_admin' => $this->max_admin ?? (Common::getConfig('max_room_admin') ?? 4),
            'name' => $this->room_name,
            'image' => $this->room_cover,
            'intro' => $this->room_intro,
            'microphone' => $this->microphone,
            'count_socket' => $this->count_room_socket,
            'is_afk' => $this->is_afk,
        ];
    }
}
