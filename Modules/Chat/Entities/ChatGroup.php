<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatGroup extends Model
{
    use HasFactory, TimestampsWithTimezone, SoftDeletes;

    protected $fillable = [
        'chat_room_id',
        'name',
        'description',
        'avatar',
        'owner_id',
        'privacy',
        'join_policy',
        'invite_token',
        'members_count',
        'max_members',
        'only_admins_post',
        'pinned_message_id',
    ];

    protected $casts = [
        'chat_room_id' => 'integer',
        'owner_id' => 'integer',
        'members_count' => 'integer',
        'max_members' => 'integer',
        'only_admins_post' => 'boolean',
        'pinned_message_id' => 'integer',
    ];

    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function pinnedMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'pinned_message_id');
    }

    public function members(): HasManyThrough
    {
        return $this->hasManyThrough(
            ChatRoomMember::class,
            ChatRoom::class,
            'id',           // ChatRoom local key referenced by chat_groups.chat_room_id
            'chat_room_id', // ChatRoomMember foreign key to ChatRoom
            'chat_room_id', // ChatGroup local key
            'id'            // ChatRoom local key
        );
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ChatGroupAuditLog::class, 'group_id');
    }

    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }
}
