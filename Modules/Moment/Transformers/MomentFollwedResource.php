<?php

namespace Modules\Moment\Transformers;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MomentfollwedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {

        $moment = $this->moment;
        $level  = Common::level_center(@$this->moment->user->id);
        if (gettype($level) == 'array') {
            $receiver_level = $level['receiver_level'] ?? 0;
            $sender_level   = $level['sender_level'] ?? 0;
            $receiver_img   = $level['receiver_img'] ?? '';
            $sender_img     = $level['sender_img'] ?? '';
        } else {
            $receiver_level = 0;
            $sender_level   = 0;
            $receiver_img   = '';
            $sender_img     = '';
        }

        $vip = @Common::ovip_center($this->moment->user->id) ?? 0;
        if (gettype($vip) == 'array') $vip_level = $vip['level']; else
            $vip_level = 0;

        return [
            'id'          => $moment->id ?? 0, 'user_id' => $moment->user_id ?? 0,
            'description' => $moment->description ?? 0, 'like_num' => $this->moment->likes->count() ?? 0,
            'comment_num' => $this->moment->comments->count() ?? 0, 'gifts_count' => $this->moment->gifts->count() ?? 0,
            'created_at'  => $moment->created_at ?? 0, 'updated_at' => $moment->updated_at ?? 0,
            'img'         => $moment->img ?? 0, 'is_like' => @$this->moment->likes_exists ?? false, 'user' => [
                'id'             => $moment->user->id ?? 0, 'uuid' => $moment->user->uuid ?? '',
                'name'           => $moment->user->name ?? '', 'image' => $moment->user?->profile?->avatar ?? '',
                'receiver_level' => $receiver_level ?? 0, // both
                'sender_level'   => $sender_level, // both
                'receiver_img'   => $receiver_img, // both
                'sender_img'     => $sender_img, 'vip' => $vip_level, // both
                'has_color_name' => Common::hasInPack($this->moment->user->id, 18), // both
            ],
        ];

    }
}
