<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordRoomGameAnswerRound extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['id', 'record_room_game_round_id', 'user_id', 'answer'];
}
