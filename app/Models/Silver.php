<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Silver extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];
}
