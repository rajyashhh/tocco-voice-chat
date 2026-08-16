<?php

namespace Modules\Tasks\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDayProgress extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $table = 'user_day_progress';

    protected $fillable = [
        'user_id',
        'day_id',
        'points',
        'is_completed',
        'created_at',
    ];

    public function day()
    {
        return $this->belongsTo(Day::class, 'day_id', 'id');
    }
}
