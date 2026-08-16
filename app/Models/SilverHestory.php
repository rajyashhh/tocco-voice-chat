<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class SilverHestory extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'silver_histories';

    protected $guarded = [];
}
