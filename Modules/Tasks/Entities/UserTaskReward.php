<?php

namespace Modules\Tasks\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTaskReward extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [
        'user_id',
        'task_reward_id',
        'created_at',
    ];
}
