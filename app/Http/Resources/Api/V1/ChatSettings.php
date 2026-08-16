<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSettings extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'chat_with_friends' => ($this->chat_with_friends == 1 ? true : false),
            // 'chat_with_followers' => ($this->chat_with_followers == 1 ? true : false),
            // 'chat_with_following' => ($this->chat_with_following == 1 ? true : false),
            'chat_with_all' => ($this->chat_with_all == 1 ? true : false),

        ];
    }
}
