<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class ChargeValue extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'charge_values';
}
