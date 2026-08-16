<?php

namespace Modules\Reals\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class RealUserView extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [];

    protected $table = 'real_user_views';

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
