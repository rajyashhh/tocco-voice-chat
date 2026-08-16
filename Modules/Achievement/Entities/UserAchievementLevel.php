<?php

namespace Modules\Achievement\Entities;

use App\Models\Admin;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Achievement\Enums\AchievementType;

class UserAchievementLevel extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = [
        'id',
        'achievement_level_id',
        'user_id',
        'gift_achievement_id',
        'unique_value',
        'end_at',
        'is_enable',
        'achievement_id',
        'custom_image',
        'picked',
        'file',
        'admin_id',
        'receive_type',
        'custom_achievement_id',
    ];

    protected $guarded = [];

    public function achievementLevel(): BelongsTo
    {
        return $this->belongsTo(AchievementLevel::class, 'achievement_level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    public function giftAchievement(): BelongsTo
    {
        return $this->belongsTo(GiftAchievement::class, 'gift_achievement_id');
    }

    public function customAchievement(): BelongsTo
    {
        return $this->belongsTo(CustomAchievement::class, 'custom_achievement_id');
    }

    // public function achievement()
    // {
    //     return $this->belongsTo(Achievement::class, 'achievement_id');
    // }

    public function scopeWithAllData(Builder $query): Builder
    {
        return $query->with([
            'achievementLevel' => function ($query) {
                $query->select(['id', 'valid_image', 'target']);
            },
            'Achievement',
        ]);
    }

    public function scopeUserPickProfile(Builder $builder): Builder
    {
        return $builder->where('picked', 1)->whereDoesntHave('achievement', fn($q) => $q->where('type', AchievementType::ROOM_TARGET->getValue()));
    }
}
