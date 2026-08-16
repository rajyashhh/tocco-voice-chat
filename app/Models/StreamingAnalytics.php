<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamingAnalytics extends Model
{
    protected $fillable = [
        'date',
        'total_sessions',
        'total_session_minutes',
        'peak_concurrent_rooms',
        'total_participants',
        'total_participant_minutes',
        'unique_participants',
        'tracks_sd',
        'tracks_hd',
        'tracks_fhd',
        'tracks_2k',
        'tracks_2k_plus',
        'tracks_audio',
        'total_messages',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get or create today's analytics record
     */
    public static function today(): self
    {
        return static::firstOrCreate(['date' => now()->toDateString()]);
    }
}
