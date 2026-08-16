<?php
namespace Modules\Chat\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
trait ChatUserTrait {
    public function chats()
    {
        return $this->belongsToMany(ChatRoom::class,'pin_to_tops');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function chatRoomsAsUser(): HasMany
    {
        return $this->hasMany(ChatRoom::class, 'user_id');
    }

    public function chatRoomsAsUser2(): HasMany
    {
        return $this->hasMany(ChatRoom::class, 'user_id2');
    }
}

