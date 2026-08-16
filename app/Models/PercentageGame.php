<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PercentageGame extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(
            User::class,               // Related model
            'percentage_game_users',   // Pivot table
            'percentage_game_id',      // Foreign key on pivot table for this model
            'user_id'                  // Foreign key on pivot table for related model
        );
    }
    
}
