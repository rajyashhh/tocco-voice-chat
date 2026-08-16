<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PaymentCoin extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $casts = [
        'fields' => 'array',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'item_id', 'id');
    }

    public function coins()
    {
        return $this->hasMany(Coin::class, 'payment_gateway_id');
    }

    public function scopeUniqueTypes($query)
    {
        return $query->select('payment_coins.*')
            ->join(
                DB::raw('(SELECT MIN(id) as id FROM payment_coins GROUP BY type) as uniq'),
                'payment_coins.id',
                '=',
                'uniq.id'
            )
            ->orderByDesc('status');
    }

    public function coinsV2()
    {
        return $this->hasMany(Coin::class, 'payment_gateway_id')
            ->withCount(['logs as usage_count' => function ($query) {
                $query->where('status', 1);
            }]);
    }

}
