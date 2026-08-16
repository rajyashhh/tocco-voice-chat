<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class VipAuth extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'vip_auth';
}
