<?php

namespace App\Models\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDayProgress extends Model
{
    use HasFactory, TimestampsWithTimezone;
}
