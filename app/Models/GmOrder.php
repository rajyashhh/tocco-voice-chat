<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class GmOrder extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'gm_orders';
}
