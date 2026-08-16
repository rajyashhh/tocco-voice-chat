<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;
     protected $guarded = [];

    protected $casts = [
        'player_list' => 'array',
        'rankList' => 'array',
        'room_destroy' => 'boolean',
    ];
}
