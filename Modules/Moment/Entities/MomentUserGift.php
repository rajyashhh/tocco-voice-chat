<?php

namespace Modules\Moment\Entities;

use App\Models\Gift;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class MomentUserGift extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'moment_user_gifts';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moment()
    {
        return $this->belongsTo(Moment::class, 'moment_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }
}
