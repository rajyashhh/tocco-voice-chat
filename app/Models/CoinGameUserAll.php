<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinGameUserAll extends Model
{
    protected $table = 'coin_game_users_all';
    public $timestamps = false;


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function game()
    {
        return $this->belongsTo(AllGame::class, 'game_id');
    }

    public function customGame()
    {
        return $this->belongsTo(AllGame::class, 'game_id', 'custom_id');
    }
}
