<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class OfficialMessage extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'official_messages';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

     public function userOfficialMessages()
    {
        return $this->hasMany(UserOfficialMessage::class);
    }
}
