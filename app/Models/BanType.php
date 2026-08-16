<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanType extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function bans()
    {
        return $this->hasMany(Ban::class, 'ban_type_id');
    }
}
