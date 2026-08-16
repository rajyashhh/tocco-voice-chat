<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        [$avatar, $cpAvatar, $nameOne, $nameTwo] = ($this->is_active == 1) ? (Common::switch_events($this->event_type) ?? "profile/g0lEsx7Joe.jpg") : null;

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'button_text' => $this->button_text,
            'image_url' => $this->image_url,
            'redirect_url' => $this->redirect_url,
            'publish_at' => $this->publish_at,
            'is_active' => (int)$this->is_active,
            'user_event_winner' =>  $avatar ?? "profile/g0lEsx7Joe.jpg",
            'user_event_winner_two' => $cpAvatar ?? "profile/g0lEsx7Joe.jpg",
            'event_type' => $this->event_type,
        ];

        if ($this->event_type == 'cp_event') {
            $data['cp_winner_name_one'] = $nameOne ?? '';
            $data['cp_winner_name_two'] = $nameTwo ?? '';
        }

        return $data;
    }
}
