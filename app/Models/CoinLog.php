<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class CoinLog extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'coin_logs';

    protected $guarded = [];


    public function owner()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id')
            ->withDefault(function ($model, $relation) {
                return new \App\Models\User();
            });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coin()
    {
        return $this->belongsTo(Coin::class, 'coin_id');
    }


    public function shippingAgency()
    {
        return $this->belongsTo(ShippingAgency::class, 'user_id');
    }
}
