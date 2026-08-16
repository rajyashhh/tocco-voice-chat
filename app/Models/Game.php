<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(CoinGameUser::class, 'game_id', 'id');
    }
}
