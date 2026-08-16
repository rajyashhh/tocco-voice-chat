<?php

namespace Modules\LuckyBox\Entities;

use App\Models\Room;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class BoxUse extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'box_uses';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function picks()
    {
        return $this->hasMany(UserBoxGift::class, 'box_uses_id');
    }

    public function box()
    {
        return $this->belongsTo(Box::class, 'box_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_uid', 'uid');
    }
    public function roomV2()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function userBoxGifts()
    {
        return $this->hasMany(UserBoxGift::class, 'box_uses_id');
    }
}
