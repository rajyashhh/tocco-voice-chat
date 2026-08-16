<?php

namespace Modules\FixedTarget\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class SpecialUser extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
