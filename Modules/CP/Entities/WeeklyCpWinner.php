<?php

namespace Modules\CP\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Events\Entities\WeeklyStar;

class WeeklyCpWinner extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function weeklyCp()
    {
        return $this->belongsTo(WeeklyStar::class, 'weekly_cp_id');
    }
}
