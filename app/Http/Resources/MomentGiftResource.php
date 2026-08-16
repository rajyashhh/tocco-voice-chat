<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentGiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $sourceName = $this->user->name ?? '';
        $giftName = $this->gift->name ?? '';
        $formattedDate = Carbon::parse($this->created_at)
            ->translatedFormat('d F Y - h:i A');

        return [
            'name' => $this->user?->name ?? '',
            'avatar' => $this->user?->profile?->avatar ?? '',
            'description' => __('source:') . $sourceName . ' ' . __('gift:') . $giftName,
            'id_moment' => $this->moment_id,
            'created_at' => $formattedDate,
            'diamond' => $this->total ?? 0,
        ];
    }
}
