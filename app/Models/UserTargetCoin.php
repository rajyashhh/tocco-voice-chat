<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTargetCoin extends Model
{
    use HasFactory, TimestampsWithTimezone;

    public $table = 'user_target_coin';

    protected $guarded = [];
}
