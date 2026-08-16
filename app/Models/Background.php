<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];
}
