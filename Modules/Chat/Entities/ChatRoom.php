<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatRoom extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $appends = ['unread_messages'];

    protected $casts = [
        'type' => 'string',
        'last_seq' => 'integer',
        'last_message_id' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)  
                    ->orderBy('id', 'desc');
    }

    public function getLastMessageCreatedAtAttribute()
    {
        $lastMessage = $this->messages()->latest()->first();

        return $lastMessage ? $lastMessage->created_at : null;
    }

    public function unReadMessages()
    {
        return $this->hasMany(ChatMessage::class)->where('status', 'not Like', 'seen');
    }
  

    public function unreadMessagesFor($userId): HasMany
    {
        return $this->hasMany(ChatMessage::class)
            ->where('user_id', '<>', $userId)
            ->where('status', '<>', 'seen');
    }

    public function getUnreadMessagesAttribute()
    {
        $userId = auth()->id();
        return $this->messages()
            ->where('user_id', '<>', $userId)
            ->where('status', '<>', 'seen')
            ->count();
    }


    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

    public function scopeBetweenUsers($query, $userId, $otherUserId)
    {
        return $query->where('user_id', $userId)->where('user_id2', $otherUserId)
            ->orWhere('user_id', $otherUserId)->where('user_id2', $userId);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatRoomMember::class, 'chat_room_id');
    }

    public function group(): HasOne
    {
        return $this->hasOne(ChatGroup::class, 'chat_room_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}
