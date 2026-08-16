<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_coupons';
}
