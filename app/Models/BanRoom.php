<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class BanRoom extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'bans_rooms';

    protected $guarded = [];

    public function room()
    {
        return $this->hasOne(Room::class, 'id', 'room_id');
    }

    // public function banType()
    // {
    //     return $this->belongsTo(BanType::class,);
    // }

    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }
}
