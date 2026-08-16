<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FairLuckTransaction extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at in migration

    protected $fillable = [
        'user_id',
        'gift_id',
        'bet_amount',
        'app_fee',
        'receiver_fee',
        'is_winner',
        'multiplier',
        'profit_amount',
        'deviation_before',
        'calculated_probability',
        'is_beginner_protected',
        'protection_multiplier',
        'room_id',
        'sender_balance_before',
        'sender_balance_after',
        'wallets_before',
        'wallets_after',
        'created_at',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'is_beginner_protected' => 'boolean',
        'created_at' => 'datetime',
        'bet_amount' => 'decimal:2',
        'app_fee' => 'decimal:2',
        'receiver_fee' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'deviation_before' => 'decimal:6',
        'calculated_probability' => 'decimal:4',
        'protection_multiplier' => 'decimal:2',
        'wallets_before' => 'array',
        'wallets_after' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }
}
