<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class StoreLog extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'store_logs';
}
