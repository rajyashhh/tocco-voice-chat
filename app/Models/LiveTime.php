<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class LiveTime extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'live_times';

    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class, 'uid', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }
}
