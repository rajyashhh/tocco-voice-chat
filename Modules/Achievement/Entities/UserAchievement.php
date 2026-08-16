<?php

namespace Modules\Achievement\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function giftAchievement(): BelongsTo
    {
        return $this->belongsTo(GiftAchievement::class, 'gift_achievement_id');
    }
}
