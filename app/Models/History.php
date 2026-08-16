<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['user_id', 'agency_id', 'diamond', 'month', 'year', 'pid'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
