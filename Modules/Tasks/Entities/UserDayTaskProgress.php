<?php

namespace Modules\Tasks\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDayTaskProgress extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'user_days_tasks_progress';
}
