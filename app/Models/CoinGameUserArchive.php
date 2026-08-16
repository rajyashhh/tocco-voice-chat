<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinGameUserArchive extends Model
{
    protected $table = 'coin_game_users_archive';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'coins',
        'type',
        'created_ym',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function game()
    {
        return $this->belongsTo(AllGame::class, 'game_id');
    }
}