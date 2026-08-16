<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBannerShow extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [
        'user_id',
        'banner_id',
    ];
    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
