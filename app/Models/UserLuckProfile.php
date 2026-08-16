<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLuckProfile extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [
        'user_id',
        'total_bets',
        'total_profit',
        'bet_count',
        'win_count',
        'first_bet_at',
        'is_legacy_user',
        'beginner_protection_ends_at',
        'current_deviation',
        'is_in_recovery',
        'recovery_target_profit',
    ];

    protected $casts = [
        'first_bet_at' => 'datetime',
        'beginner_protection_ends_at' => 'datetime',
        'is_legacy_user' => 'boolean',
        'is_in_recovery' => 'boolean',
        'total_bets' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'current_deviation' => 'decimal:6',
        'recovery_target_profit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
