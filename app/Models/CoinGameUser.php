<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinGameUser extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function game()
    {
        return $this->belongsTo(AllGame::class, 'game_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
