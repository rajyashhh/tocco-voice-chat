<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class RoomCategory extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'room_categories';

    protected $guarded = [];

    public function typeRooms()
    {
        return $this->hasMany(Room::class, 'room_type')->select('id', 'numid', 'room_name', 'room_cover', 'room_intro');
    }

    public function classRooms()
    {
        return $this->hasMany(Room::class, 'room_class')->select('id', 'numid', 'room_name', 'room_cover', 'room_intro');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
