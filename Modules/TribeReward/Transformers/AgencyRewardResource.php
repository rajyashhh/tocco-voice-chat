<?php

namespace Modules\TribeReward\Transformers;

use App\Models\OVip;
use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyRewardResource extends JsonResource
{
    protected $ware = null;

    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'type' => $this->target_type,
            'image' => $this->getImageUrl(),
            'file' => $this->getFile(),
            'file_type' => $this->getFileType(),
            'quantity' => $this->quantity,
            'available_quantity' => $this->available_quantity,
            'expire_at' => $this->expire_at ?? null,
        ];

        return $data;
    }

    protected function getWare()
    {
        if ($this->ware !== null) return $this->ware;
        return $this->ware = Ware::find($this->target);
    }

    protected function getImageUrl()
    {
        if ($this->target_type == 'ware') {
            $ware = $this->getWare();
            $path = $ware->img2 ?? $ware?->show_img;
        } elseif ($this->target_type == 'vip') {
            $vip = OVip::find($this->target);
            $path = $vip?->img;
        } elseif ($this->target_type == 'achievement') {
            $path = $this->target;
        }

        return getImagePath($path);
    }

    protected function getFile()
    {
        if ($this->target_type == 'ware') {
            $ware = $this->getWare();
            return $ware?->show_img ?? $ware?->img2 ?? '';
        }
        return '';
    }

    protected function getFileType()
    {
        if ($this->target_type == 'ware') {
            $ware = $this->getWare();
            return $ware?->image_type ?? '';
        }
        return '';
    }
}
