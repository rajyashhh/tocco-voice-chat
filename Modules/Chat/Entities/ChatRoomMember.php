<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRoomMember extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role',
        'status',
        'muted_until',
        'last_read_seq',
        'last_delivered_seq',
        'cleared_seq',
        'invited_by',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'chat_room_id' => 'integer',
        'user_id' => 'integer',
        'last_read_seq' => 'integer',
        'last_delivered_seq' => 'integer',
        'cleared_seq' => 'integer',
        'invited_by' => 'integer',
        'muted_until' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForRoom($query, $chatRoomId)
    {
        return $query->where('chat_room_id', $chatRoomId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
