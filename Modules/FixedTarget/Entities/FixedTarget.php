<?php

namespace Modules\FixedTarget\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class FixedTarget extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [
        'id',
        'diamonds',
        'hours',
        'days',
        'count_moment',
        'count_real',
        'usd',
        'agency_share',
        'img',
        'coin',
    ];
}
