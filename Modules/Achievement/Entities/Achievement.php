<?php

namespace Modules\Achievement\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Enums\AchievementType;
use Modules\Achievement\Enums\TargetType;

class Achievement extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [];

    protected $guarded = [];

    protected $casts = [
        'type' => AchievementType::class,
        'target_type' => TargetType::class,
    ];

    public function levels()
    {
        return $this->hasMany(AchievementLevel::class, 'achievement_id', 'id');
    }

    public function userAchievement()
    {
        return $this->hasManyThrough(User::class, UserAchievement::class, 'user_id', 'id');
    }

    public function userAchievments()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function userAchievementLevel()
    {
        return $this->hasMany(UserAchievementLevel::class);
    }
}
