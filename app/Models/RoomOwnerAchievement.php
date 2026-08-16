<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomOwnerAchievement extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function roomTarget()
    {
        return $this->belongsToMany(RoomGiftTarget::class, 'target_id');
    }
}
