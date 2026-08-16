<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class OffRead extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'off_reads';
}
