<?php

namespace Modules\SwitchAccount\Transformers;

use App\Models\User;
use App\Helpers\Common;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatMessage;
use Modules\SwitchAccount\Entities\UserAccount;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        $authId      = auth()->id();
        $accountId   = $this->id;

        $userAccount = UserAccount::where(function ($q) use ($authId, $accountId) {
            $q->where('parent_user_id',  $authId)
                ->where('child_user_id',   $accountId);
        })->orWhere(function ($q) use ($authId, $accountId) {
            $q->where('parent_user_id',  $accountId)
                ->where('child_user_id',   $authId);
        })
            ->first();

        $chats_id = ChatRoom::where('user_id', $this->id)->orWhere('user_id2', $this->id)->pluck('id')->toArray();
        $total_unread_message =  ChatMessage::whereIn('chat_room_id', $chats_id)->where('user_id', 'not Like', $this->id)->where('status', 'not Like', 'seen')->count();
        return [
            'id'            =>  $this->id,
            'image'         =>  $this->profile?->avatar,
            'name'          =>  $this->name,
            'uuid'          => $this->uuid,
            'user_type'     => $this->type_user,
            'sender_level'  => $this->total_sender_level ?? 0,
            'received_level'  => $this->total_received_level ?? 0,
            'unread_messages'  => $total_unread_message ?? 0,
            'key'           =>  $userAccount?->key,
            'expire'        =>  $userAccount?->expire,
            'can_switch'    => ($this->id == $authId ? false : true),
            'vip' => Common::ovip_center(@$this),
            'special_color'    => @$this->color_id ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'special_id_image'          =>  @$this->specialId?->ware?->show_img ?? "",
            'image_color'          => @$this->color_image,
            'level'=> [
                'receiver_img' => $this->getImageReceiverOrSender('receiver_id',1)->img ??'',
                'sender_img' => $this->getImageReceiverOrSender('sender_id',2)->img ??'',
            ],
            'user_types' => $this->user_types,
            'country' => @$this->country ? [
                'id' => @$this->country->id,
                'name' => @$this->country->name ?? '',
                'flag' => @$this->country->flag ?? '',
                'language' => @$this->country->language ?? '',
                'e_name' => @$this->country->e_name ?? '',
                'phone_code' => @$this->country->phone_code ?? '',
                'iso' => substr(@$this->country->iso, 0, 2),
            ] : null,
        ];
    }
}
