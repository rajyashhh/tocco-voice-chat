<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RoomBlacklist extends Model
{
    use HasFactory;

    protected $table = 'room_blacklist';

    protected $fillable = [
        'room_id',
        'user_id',
        'banned_by',
        'banned_at',
        'duration_seconds',
        'expires_at',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the room that owns the blacklist entry
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the user who is blacklisted
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who banned this user
     */
    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * Scope to get only active bans
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get non-expired bans
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get currently valid bans (active AND not expired)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->active()->notExpired();
    }

    /**
     * Scope to get expired bans
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if this ban is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at === null) {
            return true; // Permanent ban
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Check if this ban has expired
     */
    public function hasExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Permanent bans never expire
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get time remaining until ban expires (in seconds)
     */
    public function getTimeRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null; // Permanent ban
        }

        if ($this->hasExpired()) {
            return 0;
        }

        return now()->diffInSeconds($this->expires_at, false);
    }
}
