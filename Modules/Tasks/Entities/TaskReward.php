<?php

namespace Modules\Tasks\Entities;

use App\Models\Ware;
use Modules\Vip\Entities\OVip;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskReward extends Model
{
    /*protected $fillable = ['day_id', 'type', 'target', 'expire'];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($taskReward) {
            $reward = Reward::find($taskReward->type);
            $taskReward->target = $reward ? $reward->target : null;
        });
    }*/
    use HasFactory, TimestampsWithTimezone;

    public function vip()
    {
        return $this->belongsTo(OVip::class, 'target');
    }

    public function ware()
    {
        return $this->belongsTo(Ware::class, 'target');
    }

    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'target');
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if ($model->coins) {
                unset($model->coins);
            }
            if ($model->achievement) {
                unset($model->achievement);
            }
        });
    }
}
