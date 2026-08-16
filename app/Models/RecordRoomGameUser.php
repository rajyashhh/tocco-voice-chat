<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordRoomGameUser extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['id', 'record_room_game_id', 'user_id', 'status', 'team_type'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
