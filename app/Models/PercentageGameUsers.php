<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PercentageGameUsers extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function percentageGame()
    {
        return $this->belongsTo(PercentageGame::class, 'percentage_game_id');
    }
   
    
}
