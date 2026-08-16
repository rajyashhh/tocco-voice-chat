<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use TimestampsWithTimezone;
}
