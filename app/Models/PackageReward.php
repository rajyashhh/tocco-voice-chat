<?php

namespace App\Models;

use App\Helpers\Common;
use Modules\Vip\Entities\OVip;
use Illuminate\Http\UploadedFile;
use Modules\Badge\Entities\Badge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PackageReward extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $appends = ['target1', 'target2', 'target3', 'target4', 'target5'];

    public function getAppends2(): array
    {
        return $this->getAppends();
    }
    public function superPackage()
    {
        return $this->belongsTo(SuperPackageReward::class, 'super_package_id ');
    }
    public function ware(): HasOne
    {
        return $this->hasOne(Ware::class, 'id', 'target');
    }
    
    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'target');
    }
    
    public function vip(): HasOne
    {
        return $this->hasOne(OVip::class, 'id', 'target');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(Badge::class, 'id', 'target');
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
        self::creating(function ($model) {
            if ($model->target_type === 'ware') {
                $model->target = request('target1', $model->target);
            } elseif ($model->target_type === 'vip') {
                $model->target = request('target2', $model->target);
            } elseif ($model->target_type === 'coins') {
                $model->target = request('target3', $model->target);
            } elseif ($model->target_type === 'badge') {
                $model->target = request('target5', $model->target);
            } elseif ($model->target_type === 'achievement') {

                $model->target = request('target4', $model->target);
            }
            unset($model->target1);
            unset($model->target2);
            unset($model->target3);
            unset($model->target4);
            unset($model->target5);
        });

        self::updating(function ($model) {
            if ($model->target_type === 'ware') {
                $model->target = request('target1', $model->target);
            } elseif ($model->target_type === 'vip') {
                $model->target = request('target2', $model->target);
            } elseif ($model->target_type === 'coins') {
                $model->target = request('target3', $model->target);
            } elseif ($model->target_type === 'badge') {
                $model->target = request('target5', $model->target);
            } elseif ($model->target_type === 'achievement') {
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
