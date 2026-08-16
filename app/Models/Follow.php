<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Moment\Entities\Moment;

class Follow extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function followed()
    {
        return $this->belongsTo(User::class, 'followed_user_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moments()
    {
        return $this->hasMany(Moment::class, 'user_id', 'followed_user_id')->orderByDesc('id');
    }

    public function room()
    {
        return $this->hasOne(Room::class, 'uid', 'followed_user_id');
    }
}
