<?php

namespace Modules\Tasks\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'days';

    public function userDaysProgress()
    {
        return $this->hasMany(UserDayProgress::class, 'day_id', 'id');
    }
}
