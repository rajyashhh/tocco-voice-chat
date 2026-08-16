<?php

namespace Modules\TaskStream\Entities;

use App\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStreamRoom extends Model
{
    protected $fillable = ['task_stream_id', 'room_id'];

    public function taskStream(): BelongsTo
    {
        return $this->belongsTo(TaskStream::class, 'task_stream_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
