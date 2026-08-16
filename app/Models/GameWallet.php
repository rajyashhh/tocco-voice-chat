<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameWallet extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function scopeFilterByMonth(Builder $query): Builder
    {
        // Indexed equality on the generated `month` column (and year-correct,
        // unlike the old whereMonth which matched the same month of any year).
        return $query->where('month', now()->format('Y-m'));
    }

    /**
     * Current month's wallet row. If the month rolled over and no row exists yet,
     * carry the remaining cap forward (same semantics as app:update-game-wallet)
     * instead of silently closing all games.
     */
    public static function currentMonth(): self
    {
        $wallet = static::filterByMonth()->orderBy('id')->first();
        if ($wallet) {
            return $wallet;
        }

        $previous = static::orderByDesc('id')->first();

        return static::create([
            'balance' => $previous ? $previous->balance - $previous->used : 0,
            'used' => 0,
        ]);
    }
}
