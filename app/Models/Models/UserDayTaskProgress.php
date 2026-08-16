<?php

namespace App\Models\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDayTaskProgress extends Model
{
    use HasFactory, TimestampsWithTimezone;
}
