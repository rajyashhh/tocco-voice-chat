<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NowpaymentOrder extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $table = 'nowpayments_orders';
}
