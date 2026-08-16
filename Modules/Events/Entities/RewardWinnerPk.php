<?php

namespace Modules\Events\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardWinnerPk extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function reward()
    {
        return $this->belongsTo(PkReward::class, 'pk_reward_id');
    }

    public function winner()
    {
        return $this->hasOne(User::class, 'id', 'pk_winner_id');
    }
}
