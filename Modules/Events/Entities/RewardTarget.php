<?php

namespace Modules\Events\Entities;

use App\Models\Ware;
use App\Helpers\Common;
use Modules\Vip\Entities\OVip;
use Illuminate\Http\UploadedFile;
use Modules\Badge\Entities\Badge;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RewardTarget extends Model
{
    use HasFactory, TimestampsWithTimezone;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $appends = ['target1', 'target2', 'target3', 'target4', 'target5'];

    protected $table = 'reward_charges';

    public function target()
    {
        return $this->belongsTo(ChargeTargetEvent::class, 'charge_event_id');
    }
    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'target');
    }
    public function ware()
    {
        return $this->hasOne(Ware::class, 'id', 'target');
    }

    public function vip()
    {
        return $this->hasOne(OVip::class, 'id', 'target');
    }

    public function badge()
    {
        return $this->hasOne(Badge::class, 'id', 'target');
    }

    public function getTarget5Attribute()
    {
        return $this->target;
    }

    public function getTarget1Attribute()
    {
        return $this->target;
    }

    public function getTarget2Attribute()
    {
        return $this->target;
    }

    public function getTarget3Attribute()
    {
        return $this->target;
    }

    public function getTarget4Attribute()
    {
        return $this->target;
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if ($model->type === 'ware') {
                $model->target = request('target1', $model->target);
            } elseif ($model->type === 'vip') {
                $model->target = request('target2', $model->target);
            } elseif ($model->type === 'coins') {
                $model->target = request('target3', $model->target);
            } elseif ($model->type === 'badge') {
                $model->target = request('target5', $model->target);
            } elseif ($model->type === 'achievement') {
                $model->target = request('target4', $model->target);
            }
            unset($model->target1);
            unset($model->target2);
            unset($model->target3);
            unset($model->target4);
            unset($model->target5);
        });

        self::updating(function ($model) {
            if ($model->type === 'ware') {
                $model->target = request('target1', $model->target);
            } elseif ($model->type === 'vip') {
                $model->target = request('target2', $model->target);
            } elseif ($model->type === 'coins') {
                $model->target = request('target3', $model->target);
            } elseif ($model->type === 'badge') {
                $model->target = request('target5', $model->target);
            } elseif ($model->type === 'achievement') {
                $model->target = request('target4', $model->target);
            }
            unset($model->target1);
            unset($model->target2);
            unset($model->target3);
            unset($model->target4);
            unset($model->target5);
        });
    }
}
