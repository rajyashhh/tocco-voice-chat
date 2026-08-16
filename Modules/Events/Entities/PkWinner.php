<?php

namespace Modules\Events\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PkWinner extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pkEvent()
    {
        return $this->belongsTo(PkEvent::class, 'pk_event_id');
    }

    public function rewardsPk()
    {
        return $this->belongsToMany(PkReward::class, 'reward_winner_pks', 'pk_winner_id', 'pk_reward_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
