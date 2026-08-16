<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamingRoomSession extends Model
{
    protected $fillable = [
        'room_name',
        'room_sid',
        'owner_user_id',
        'started_at',
        'finished_at',
        'duration_minutes',
        'peak_participants',
        'total_participants',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function participantSessions(): HasMany
    {
        return $this->hasMany(StreamingParticipantSession::class, 'room_session_id');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(StreamingTrack::class, 'room_session_id');
    }

    /**
     * Calculate duration when room finishes
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->finished_at) {
            $this->duration_minutes = $this->started_at->diffInMinutes($this->finished_at);
            $this->save();
        }
    }
}
