<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class BlackList extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'black_lists';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function blockedPerson()
    {
        return $this->belongsTo(User::class, 'from_uid');
    }

    public function scopeBetweenUsers($query, $userId, $otherUserId)
    {
        return $query->where('user_id', $userId)->where('from_uid', $otherUserId)
            ->orwhere('user_id', $otherUserId)->where('from_uid', $userId);
    }


    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_uid');
    }
}
