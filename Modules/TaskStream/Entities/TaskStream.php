<?php

namespace Modules\TaskStream\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStream extends Model
{
    protected $fillable = ['room_id', 'mix_id', 'is_remote'];

    public function rooms(): HasMany
    {
        return $this->hasMany(TaskStreamRoom::class);
    }
}
