<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingUserResource extends JsonResource
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
            'user_id' => $this->id,
            'color_name' => $this->color_name,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'frame' => $this->frame,
            'frame_id' => $this->frame_id,
            'type_user' => $this->type_user,
            'manger_type' => $this->manger_type,
            'vip_level' => $this->vip_level,
            'sender_level' => $this->sender_level,
            'reciver_level' => $this->reciver_level,
            'vip_level_img' => $this->vip_level_img,
            'sender_level_img' => $this->sender_level_img,
            'reciver_level_img' => $this->reciver_level_img,
            'country' => [
                'id' => optional($this->country)['id'],
                'name' => optional($this->country)['name'],
                'flag' => optional($this->country)['flag'],
                'language' => optional($this->country)['language'],
                'e_name' => optional($this->country)['e_name'],
                'phone_code' => optional($this->country)['phone_code'],
                'iso' => optional($this->country)['iso'],
            ],
            'age' => $this->age,
            'achievement_images' => collect($this->achievement_images)->map(function ($item) {
                return [
                    'image' => $item['image'],
                    'title' => $item['title'],
                    'created_at' => $item['created_at'],
                ];
            }),
        ];
    }
}
