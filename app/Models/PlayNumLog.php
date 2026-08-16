<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class PlayNumLog extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'play_num_logs';
}
