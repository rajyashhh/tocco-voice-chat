<?php

namespace Modules\RoomBoom\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomBoomLevel extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = ['level', 'min_target', 'target','image_type'];

    protected static function booted(): void
    {
        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::forget('room_boo_levels');
            \Illuminate\Support\Facades\Cache::forget('room_boom_levels');
            \Illuminate\Support\Facades\Cache::forget('boom_levels:videos');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function roomBoomRewards(): HasMany
    {
        return $this->hasMany(RoomBoomReward::class);
    }

    public function roomBooms(): HasMany
    {
        return $this->hasMany(RoomBoom::class);
    }
}
