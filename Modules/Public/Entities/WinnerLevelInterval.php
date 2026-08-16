<?php

namespace Modules\Public\Entities;

use App\Models\User;
use App\Models\Ware;
use Modules\Vip\Entities\OVip;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class WinnerLevelInterval extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function levelInterval()
    {
        return $this->belongsTo(LevelInterval::class, 'level_interval_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

   

    public function rewardLevelInterval()
    {
        return $this->belongsTo(RewardLevelInterval::class, 'reward_level_interval_id');
    }
}
