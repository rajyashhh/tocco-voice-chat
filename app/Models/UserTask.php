<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_tasks';
}
