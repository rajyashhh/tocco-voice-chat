<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Taggable extends Model
{
    use TimestampsWithTimezone;
}
