<?php

namespace Modules\TaskStream\Entities;

use Illuminate\Database\Eloquent\Model;

class TaskStreamInvitation extends Model
{
    protected $fillable = ['task_stream_id', 'inviter_user_id', 'invitee_user_id', 'status'];
}
