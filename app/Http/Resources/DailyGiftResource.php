<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyGiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->gift_type == 'ware') {
            $ware = Ware::find($this->target);
            $imagePath = $ware->img2 ?? $ware->show_img ?? 'default.png';
        } elseif ($this->gift_type == 'vip') {
            $vip = OVip::find($this->target);
            $imagePath = $vip->img ?? 'default.png';
        } elseif ($this->gift_type == 'achievement') {
            $imagePath = $this->target;
        } else {
            $imagePath = 'coin.png'; // Default for 'coins'
        }

        return [
            'id'        => $this->id,
            'order'     => $this->order,
            'gift_type' => $this->gift_type,
            'image'     => getImagePath($imagePath), // Convert to full URL
            'target' => $this->target,
            'expir'     => $this->expir,
        ];
    }
}
