<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordRoomGame extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['id', 'room_id', 'room_game_id', 'user_id', 'type', 'coins', 'player_win_id', 'round_num', 'current_round'];

    public function players()
    {
        return $this->hasMany(RecordRoomGameUser::class, 'record_room_game_id');
    }
}
