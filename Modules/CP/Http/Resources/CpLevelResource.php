<?php

namespace Modules\CP\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CpLevelResource extends JsonResource
{
    public function toArray($request)
    {
        // Transform the levels and their gifts
        return $this->levels->map(function ($level) {
            // Check if the user has this level
            $level->have = in_array($level->id, $this->userLevelIds);

            // Group and transform gifts
            $level->gifts = $level->gifts->groupBy('type')->map(function ($gifts, $type) {
                if (in_array($type, ['coins', 'achievement'])) {
                    // Fixed image logic
                    $fixedImages = [
                        'coins' => 'fixed_coins_image.png',
                        'achievement' => 'fixed_achievement_image.png',
                    ];

                    return [
                        'type' => $type,
                        'image' => $fixedImages[$type] ?? null,
                    ];
                }

                // Return images for other types
                return [
                    'type' => $type,
                    'images' => $type == 'vip'
                        ? $gifts->pluck('vip.img')->filter() // Get VIP images
                        : $gifts->pluck('ware.show_img')->filter(), // Get Ware images
                ];
            })->values(); // Reset array keys

            return $level;
        });
    }
}
