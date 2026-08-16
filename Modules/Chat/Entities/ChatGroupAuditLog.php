<?php

namespace Modules\Chat\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupAuditLog extends Model
{
    use HasFactory, TimestampsWithTimezone;

    const UPDATED_AT = null;

    protected $fillable = [
        'group_id',
        'actor_id',
        'target_id',
        'action',
        'meta',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'actor_id' => 'integer',
        'target_id' => 'integer',
        'meta' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
