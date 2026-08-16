<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Mic extends Model
{
    use TimestampsWithTimezone;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
