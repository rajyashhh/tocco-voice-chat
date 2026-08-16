<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCodeInvitation extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['id', 'user_id', 'code', 'invited_id', 'invited_charge', 'user_percentage'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invited()
    {
        return $this->belongsTo(User::class, 'invited_id')->with('profile:id,user_id,avatar as image');
    }
}
