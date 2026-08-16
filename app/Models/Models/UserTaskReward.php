<?php

namespace App\Models\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTaskReward extends Model
{
    use HasFactory, TimestampsWithTimezone;
}
