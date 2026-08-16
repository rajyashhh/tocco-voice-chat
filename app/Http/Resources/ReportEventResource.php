<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = '';
        if ($this->reward->type == 'coins') {
            $target = $this->reward->target;
        } elseif ($this->reward->type == 'vip') {
            $target = $this->reward->vip->name;
        } elseif ($this->reward->type == 'ware') {
            $target = $this->reward->ware->name;
        }

        $path = "";
        if ($this->reward->type == 'ware') {
            $path  = $this->reward->ware->img2 ?? ($this->reward->ware->show_img ?? '');
        } elseif ($this->reward->type == 'vip') {
            $path = $this->reward->vip->img ?? '';
        } elseif ($this->reward->type == 'achievement') {
            $path = $this->reward->target ?? '';
        } else {
            $path = 'coin.png';
        }
        return [
            'id' => $this->id,
            'winner' => [
                'id' => $this->winner->id ?? 0,
                'name' => $this->winner->name ?? '',
                'uuid' => $this->winner->uuid ?? 0,
                'image' => @$this->winner->profile->avatar ?? '',
            ],
            'reward' => [
                'id'    => $this->reward->id ?? 0,
                'level' => $this->reward->level ?? 0,
                'type' => $this->reward->type ?? '',
                'gift' => $target,
                'image' => $path,
            ],

        ];
    }
}
