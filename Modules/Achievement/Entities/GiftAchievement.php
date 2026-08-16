<?php

namespace Modules\Achievement\Entities;

use App\Models\Gift;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftAchievement extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function Achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }
}
