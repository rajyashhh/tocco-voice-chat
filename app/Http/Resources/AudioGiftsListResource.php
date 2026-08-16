<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioGiftsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $roomName = $this->room->room_name ?? '';
        $giftName = $this->gift->name ?? '';
        $formattedDate = Carbon::parse($this->created_at)
            ->translatedFormat('d F Y - h:i A');

        return [
            'name' => $this->sender?->name ?? '',
            'avatar' => $this->sender?->profile?->avatar ?? '',
            'sender_id' => $this->sender?->id ?? 0,
            'description' => __('source:') . $roomName . ' ' . __('gift:') . $giftName,
            'created_at' => $formattedDate,
            'diamond' => $this->total ?? 0,
        ];
    }
}
