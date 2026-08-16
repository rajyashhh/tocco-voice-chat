<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class NewProfileResource extends JsonResource
{
    public function toArray($request)
    {

        $vip_level_img  = Common::ovip_center_rank_img(@$this->id);
        $total_received_level_img = Common::getImageTotalReceiverOrSender($this->total_received_level);
        $total_sender_level_img = Common::getImageTotalReceiverOrSender($this->total_sender_level);
        $chatRoom = $this->chatRoomsAsUser->first() ?? $this->chatRoomsAsUser2->first();

        $userId = $request->user()->id;
//        $is_licked = $this->likes?->where("likeable_id",$userId)->exist();
//        $is_ignored = $this->ignores?->where("ignore_user_id",$userId)->exist();
        return [
            'id' => $this->id,
            'uuid'=>@$this->uuid,
            'name' => $this->name,
            'image'=>@$this->profile?->avatar,
            'bio'=>@$this->bio,
            'distance'=>$this->distance ?? 3.3,
            'liked'=>$this->likes_exists ?? false,
            "multi_images"          => $this->images?->select("img"),
            'gender' => intval(@$this->type_user) ?: 0,
            'vip_level_img' =>  $vip_level_img ?? '',
            'sender_level_img' =>$total_sender_level_img->img ?? '',
            'reciver_level_img' => $total_received_level_img->img ?? '',
            'has_color_name'=>Common::hasInPack ($this->id,18,true), // both
            'age'       => @Carbon::parse ($this->profile?->birthday)->age ?? 0,
            'vip' => Common::ovip_center($this),
            'is_friend'            => $this->isFriends(),
            'chat_id' => $chatRoom->id ?? null,
            'deleted_at' => $this->deleted_at,
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
        ];
    }
}
