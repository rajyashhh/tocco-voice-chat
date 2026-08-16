<?php

namespace Modules\TaskStream\Entities;

use Illuminate\Database\Eloquent\Model;

class PkSession extends Model
{
    protected $fillable = ['task_stream_id', 'team_1', 'team_2', 'status', 'winner', 'team_1_score', 'team_2_score', 'ends_at'];

}
