<?php

namespace Modules\Events\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $fillable = ['id', 'weekly_star_id', 'user_id', 'level'];

    public function weeklyEvent()
    {
        return $this->belongsTo(WeeklyStar::class, 'weekly_star_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rewards()
    {
        return $this->belongsToMany(Reward::class, 'winner_rewards', 'winner_id', 'reward_id');
    }
}
