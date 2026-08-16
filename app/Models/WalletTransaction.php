<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'description',
        'description_data',
        'transactions_type',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'user_id', 'user_id');
    }
}
