<?php

namespace Modules\RoleRewards\Entities;

use App\Helpers\Common;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class RoleReward extends Model
{
    protected $appends = ['rewardable_id2', 'rewardable_id3', 'rewardable_id1', 'reward_achievement'];
    protected $fillable = [
        'role_id',
        'rewardable_id',
        'rewardable_type',
        'type',
        'expire',
        'reward_achievement'
    ];

    /**
     * العلاقة مع الدور
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function getRewardableId2Attribute()
    {
        return $this->rewardable_id;
    }

    public function getRewardableId3Attribute()
    {
        return $this->rewardable_id;
    }

    public function getRewardableId1Attribute()
    {
        return $this->rewardable_id;
    }

    public function getRewardAchievementAttribute()
    {
        return $this->rewardable_id;
    }

    /**
     * العلاقة المورفية
     */
    public function rewardable()
    {
        return $this->morphTo();
    }


    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (request('type') === 'ware') {
                $model->rewardable_id = request('rewardable_id1', $model->rewardable_id);
            } elseif (request('type') === 'vip') {
                $model->rewardable_id = request('rewardable_id2', $model->rewardable_id);
            } elseif (request('type') === "badge") {
                $model->rewardable_id = request('rewardable_id3', $model->rewardable_id);
            } elseif (request('type') === "achievement") {
                $model->rewardable_id = request('reward_achievement', $model->rewardable_id);
            }
            $model->type = request('type');
            unset($model->rewardable_id2);
            unset($model->rewardable_id3);
            unset($model->rewardable_id1);
            unset($model->reward_achievement);
        });

        self::updating(function ($model) {
            if (request('type') === 'ware') {
                $model->rewardable_id = request('rewardable_id1', $model->rewardable_id);
            } elseif (request('type') === 'vip') {
                $model->rewardable_id = request('rewardable_id2', $model->rewardable_id);
            } elseif (request('type') === 'badge') {
                $model->rewardable_id = request('rewardable_id3', $model->rewardable_id);
            } elseif (request('type') === "achievement") {
                $model->rewardable_id = request('reward_achievement', $model->rewardable_id);
            } else {
                $model->rewardable_id = 0;
            }
            $model->type = request('type');
            unset($model->rewardable_id2);
            unset($model->rewardable_id1);
            unset($model->rewardable_id3);
            unset($model->reward_achievement);
        });
    }
}
