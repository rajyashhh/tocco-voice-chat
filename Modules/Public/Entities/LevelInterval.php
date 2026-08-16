<?php

namespace Modules\Public\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class LevelInterval extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function rewards()
    {
        return $this->hasMany(RewardLevelInterval::class, 'level_interval_id');
    }
}
