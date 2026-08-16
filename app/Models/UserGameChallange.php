<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGameChallange extends Model
{
    use HasFactory, TimestampsWithTimezone;

    public function player_one()
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    public function player_two()
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }
}
