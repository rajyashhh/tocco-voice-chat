<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coin extends Model
{
    use SoftDeletes, TimestampsWithTimezone;

    protected $guarded = [];

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function paymentCoin(): BelongsTo
    {
        return $this->belongsTo(PaymentCoin::class, 'payment_gateway_id');
    }
    public function logs()
    {
        return $this->hasMany(CoinLog::class, 'coin_id');
    }

}
