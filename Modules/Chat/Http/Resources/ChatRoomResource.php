<?php

namespace Modules\Chat\Http\Resources;

use App\Helpers\Common;
use Modules\Chat\Entities\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{

    public function toArray(Request $request)
    {

        if ($this->user_id !== $request->user()->id) {
            $user  = User::withTrashed()->find($this->user_id2);
            $user2 = User::withTrashed()->find($this->user_id);
        } else {
            $user  = User::withTrashed()->find($this->user_id);
            $user2 = User::withTrashed()->find($this->user_id2);
        }

        $total_undread_message = ChatMessage::where('chat_room_id', $this->id)->where('user_id', 'not Like', $user->id)->where('status', 'not Like', 'seen')->count();
        $hasColor = Common::hasInPack($user2, 18, true);

        return [
            'user_id'             => @$user2->id,
            'name'                => @$user2->name,
            'img'                 => $user2?->profile?->avatar,
            'deleted_at'          => @$user2->deleted_at,
            'in_room'             => @$user2?->now_room_uid ? true : false,
            'chat_id'             => $this->id,
            'unread_message'      => $total_undread_message,
            'colored_name'        => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip($user2, 18, 'color') : null),
            'last_message' => @new ChatMessageV2Resource(
                $this->messages
                    // ->filter(function ($msg) {
                    //     return is_null($msg->user_1_deleted)
                    //         || (!is_null($msg->user_1_deleted) && !is_null($msg->user_2_deleted));
                    // })
                    ->sortByDesc('id')
                    ->first()
            ),
            ];
    }
}
