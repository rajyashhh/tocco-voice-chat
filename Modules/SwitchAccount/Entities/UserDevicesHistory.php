<?php

namespace Modules\SwitchAccount\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevicesHistory extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
