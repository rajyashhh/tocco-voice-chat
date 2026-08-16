<?php

namespace Modules\TribeReward\Transformers;

use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vip\Entities\OVip;

class TribeRewardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => $this->target_type,
            'image' => $this->getImageUrl(),
            'title' => $this->getTitle() . ($this->expire_days ? ' - ' . $this->expire_days . ' days' : ''),
            'count' => $this->quantity,
        ];
    }

    protected function getImageUrl()
    {
        if ($this->target_type == 'ware') {
            $ware = Ware::find($this->target);
            $path = $ware->img2 ?? $ware?->show_img;
        } elseif ($this->target_type == 'vip') {
            $vip = OVip::find($this->target);
            $path = $vip?->img;
        } elseif ($this->target_type == 'achievement') {
            $path = $this->target;
        }

        return getImagePath($path);
    }

    protected function getTitle()
    {
        if ($this->target_type == 'ware') {
            $ware = Ware::find($this->target);
            return $ware?->name ?? '';
        } elseif ($this->target_type == 'vip') {
            $vip = OVip::find($this->target);
            return $vip?->name ?? '';
        } elseif ($this->target_type == 'achievement') {
            return 'Achievement';
        } else {
            return '';
        }
    }
}
