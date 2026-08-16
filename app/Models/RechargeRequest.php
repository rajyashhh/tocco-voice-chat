<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class RechargeRequest extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'recharge_requests';
}
