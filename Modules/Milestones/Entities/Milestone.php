<?php

namespace Modules\Milestones\Entities;

use App\Helpers\Common;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Milestone extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = ['name','slug' , 'description', 'is_active'];

    protected static function booted(): void
    {
        static::saved(function ($milestone) {
            \Cache::forget('milestone_area_manager');
            \Cache::forget('milestone_super_admin');
        });
        static::deleted(function ($milestone) {
            \Cache::forget('milestone_area_manager');
            \Cache::forget('milestone_super_admin');
        });
    }



    public function rewards()
    {
        return $this->hasMany(MilestoneReward::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_milestones')
                    ->withPivot('achieved_at')
                    ->withTimestamps();
    }
}
