<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
 

    public function banType()
    {
        return $this->belongsTo(BanType::class);
    }

    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }
}
