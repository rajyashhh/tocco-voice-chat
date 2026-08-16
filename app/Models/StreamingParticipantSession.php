<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingParticipantSession extends Model
{
    protected $fillable = [
        'room_session_id',
        'participant_identity',
        'participant_name',
        'joined_at',
        'left_at',
        'duration_minutes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function roomSession(): BelongsTo
    {
        return $this->belongsTo(StreamingRoomSession::class, 'room_session_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_identity');
    }

    /**
     * Calculate duration when participant leaves
     */
    public function calculateDuration(): void
    {
        if ($this->joined_at && $this->left_at) {
            $this->duration_minutes = $this->joined_at->diffInMinutes($this->left_at);
            $this->save();
        }
    }
}
