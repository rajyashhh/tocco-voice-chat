<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingTrack extends Model
{
    protected $fillable = [
        'room_session_id',
        'participant_identity',
        'track_type',
        'video_quality',
        'video_width',
        'video_height',
        'published_at',
        'unpublished_at',
        'duration_minutes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
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
     * Calculate duration when track is unpublished
     */
    public function calculateDuration(): void
    {
        if ($this->published_at && $this->unpublished_at) {
            $this->duration_minutes = $this->published_at->diffInMinutes($this->unpublished_at);
            $this->save();
        }
    }

    /**
     * Determine video quality based on height
     */
    public static function determineVideoQuality(?int $height): ?string
    {
        if (!$height) {
            return null;
        }

        return match (true) {
            $height >= 2160 => '4k',      // 4K
            $height > 1440  => '2k_plus', // فوق 2K
            $height >= 1440 => '2k',      // 2K/1440p
            $height >= 1080 => 'fhd',     // Full HD
            $height >= 720  => 'hd',      // HD
            default         => 'sd',      // SD
        };
    }
}
