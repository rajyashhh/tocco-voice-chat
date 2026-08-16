<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class EnteredRoom extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'entered_rooms';

    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class, 'rid', 'id');
    }
}
