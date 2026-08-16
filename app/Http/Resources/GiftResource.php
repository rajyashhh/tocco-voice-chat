<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
//        $userId = request('user_id') ?? request()->user()->id;
//        $hasPivotType11 = $request->query('type') == 11 && isset($this->pivot);
        $giftLogs = $this->additional['gift_total'] ?? null;
        return [
            'id' => $this->id,
            'name' =>  $this->name,
            // 'type' => $this->type = 1 ? 'normal' : 'hot',
            'type' => $this->category?->type ?? 'normal',
            'price' => $this->price ?: 0,
            'img' => $this->img ?: '',
            'show_img' => $this->show_img ?: '',
            'show_img2' => $this->show_img2 ?: '',
            'vip_level' => $this->vip_level ?: 0,
            'is_on' => ($this->vip_level <= Common::getLevel($this->receiver, 3, giftLogs: $giftLogs)) ? 1 : 0,
            'music_gift' => $this->music_gift ? 1 : 0,
            'international_gift' => $this->international_gift ? 1 : 0,
            'image_type' => $this->image_type ?? '',
            'quantity' => $this->when(
                $request->query('type') == 11 && isset($this->pivot) && isset($this->pivot->quantity),
                function () {
                    return $this->pivot->quantity;
                }
            ),

            'expire' => $this->when(
                $request->query('type') == 11 && isset($this->pivot) && isset($this->pivot->expire),
                function () {
                    return $this->pivot->expire;
                }
            ),
        ];
    }
}
