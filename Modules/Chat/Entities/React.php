<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class React extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function scopeFindReact($query, $chatRoomId, $messageId, $userId)
    {
        return $query->where('chat_room_id', $chatRoomId)
            ->where('chat_message_id', $messageId)
            ->where('user_id', $userId);
    }
}
