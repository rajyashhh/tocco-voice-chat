<?php

namespace Modules\Badge\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = request()->user();
        $userLang = $user->lan ?? 'en';

        $badgeImage = $this->badge?->images?->firstWhere('language', $userLang);

        if (!$badgeImage || !$badgeImage->image) {
            $badgeImage = $this->badge?->images?->firstWhere('language', 'default');
        }

        if (!$badgeImage || !$badgeImage->image) {
            $badgeImage = $this->badge?->images?->first();
        }

        return [
            'image' => $badgeImage?->image ?? $this->badge?->image ?? '',
            'image_type' => $badgeImage?->image_type ?? $this->badge?->image_type ?? '',
        ];
    }
}
