<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSalary extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
