<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class PackLog extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'pack_logs';

    protected $guarded = [];
}
