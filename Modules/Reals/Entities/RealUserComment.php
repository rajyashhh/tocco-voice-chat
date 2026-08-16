<?php

namespace Modules\Reals\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class RealUserComment extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
