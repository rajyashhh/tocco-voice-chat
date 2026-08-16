<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'time_logs';
}
