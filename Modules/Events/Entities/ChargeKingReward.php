<?php

namespace Modules\Events\Entities;

use App\Models\Ware;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Achievement\Entities\CustomAchievement;
use Modules\Badge\Entities\Badge;
use Modules\Vip\Entities\OVip;

class ChargeKingReward extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

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

    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'target');
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

    public function getTarget5Attribute()
    {
        return $this->target;
    }

    protected static function boot()
    {
        parent::boot();

        $resolveTarget = function ($model) {
            if ($model->type === 'ware') {
                $model->target = request('target1', $model->target);
                $ware = Ware::find($model->target);
                if ($ware) {
                    if ($ware->type === 4) {
                        $model->sub_type = 'bubble';
                    } elseif ($ware->type === 5) {
                        $model->sub_type = 'intro';
                    } elseif ($ware->type === 6) {
                        $model->sub_type = 'frame';
                    }
                }
            } elseif ($model->type === 'vip') {
                $model->target = request('target2', $model->target);
            } elseif ($model->type === 'coins') {
                $model->target = request('target3', $model->target);
            } elseif ($model->type === 'achievement') {
                $model->target = request('target4', $model->target);
            } elseif ($model->type === 'badge') {
                $model->target = request('target5', $model->target);
            }
            unset($model->target1);
            unset($model->target2);
            unset($model->target3);
            unset($model->target4);
            unset($model->target5);
        };

        self::creating($resolveTarget);
        self::updating($resolveTarget);
    }
}