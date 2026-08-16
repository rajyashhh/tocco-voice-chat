<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use TimestampsWithTimezone;

    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
