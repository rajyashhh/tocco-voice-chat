<?php

namespace Modules\RankingReward\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WinnerRanking extends Model
{
    use HasFactory;
    protected $fillable = ['type', 'winner_id', 'reward_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function reward()
    {
        return $this->belongsTo(RankingReward::class, 'reward_id');
    }
}
