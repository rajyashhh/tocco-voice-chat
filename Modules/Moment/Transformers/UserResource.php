<?php

namespace Modules\Moment\Transformers;

use App\Helpers\UserPackHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\MangerTypeResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        $chatRoom = null;

        if ($this->relationLoaded('chatRoomsAsUser') || $this->relationLoaded('chatRoomsAsUser2')) {
            $chatRoom = $this->chatRoomsAsUser->first() ?? $this->chatRoomsAsUser2->first();
        }

        return [
            'id'                 => @$this->id, // both
            'uuid'               => @$this->uuid ?? '', // both
            'name'               => @$this->name ?: '', // both
            'image'              => $this->profile?->avatar ?: '', // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'receiver_level'     => $this->receiverLevel?->level ?? 0, // both
            'sender_level'       => $this->senderLevel?->level ?? 0, // both
            'receiver_img'       => $this->receiverLevel?->img ?? '', // both
            'sender_img'         => $this->senderLevel?->img, // both
            'vip'                => $this->UserVip?->level , // both
            'new_vip'                =>  [
                'vip_img'      => UserPackHelper::getVipIcon($this->resource),
                'colored_name' => UserPackHelper::getColorName($this->resource),
            ],// both
            'has_color_name'     => (bool) UserPackHelper::getColorName($this->resource), // both
            'color_name'   => UserPackHelper::getColorName($this->resource) ,

            'frame_id'           => UserPackHelper::getFrameId($this->resource),
            'frame'              => UserPackHelper::getFrameImage($this->resource),
            'senderLevel'        => $this->total_sender_level,
            'reciverLevel'        => $this->total_received_level,
            'now_room'             => [
                'is_in_room'      => @$this->now_room_uid != 0,
                'uid'             => @(int)$this->now_room_uid,
                'is_mine'         => @$this->id == $this->now_room_uid,
                'password_status' => (bool) $this->room?->room_pass
            ],
            'type_user'            => intval(@$this->type_user) ?: 0, // both
            "manger_type"          => new MangerTypeResource(@$this->mangerType),
            'age'    => Carbon::parse($this->profile?->birthday)->age,
            'gender' => $this->profile?->gender ?? 1,
            'is_follow'            => $this->is_follow,
            'is_friend'            => $this->isFriends(),
            'image_color'          => @$this->color_image ?? '',
            'special_color'    => @$this->color_id ?? '',
            'user_types' => $this->user_types,
            'chat_id' => $chatRoom->id ?? null,
            'deleted_at' => $this->deleted_at,
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
        ];
    }
}
