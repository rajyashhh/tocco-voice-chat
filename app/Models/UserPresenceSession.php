<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresenceSession extends Model
{
    protected $fillable = [
        'user_id',
        'connected_at',
        'disconnected_at',
        'duration_minutes',
        'device_type',
        'app_version',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate session duration
     */
    public function calculateDuration(): void
    {
        if ($this->connected_at && $this->disconnected_at) {
            $this->duration_minutes = $this->connected_at->diffInMinutes($this->disconnected_at);
            $this->save();
        }
    }
}
