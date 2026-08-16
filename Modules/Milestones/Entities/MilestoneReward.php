<?php

namespace Modules\Milestones\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MilestoneReward extends Model
{
    protected $appends = ['rewardable_id2', 'rewardable_id3', 'reward1', 'reward2'];
    protected $fillable = [
        'milestone_id',
        'rewardable_id',
        'rewardable_type',
        'reward',
        'type',
        'expire',
    ];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function rewardable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRewardableId2Attribute()
    {
        return $this->rewardable_id;
    }

    public function getRewardableId3Attribute()
    {
        return $this->rewardable_id;
    }

    public function getReward1Attribute()
    {
        return $this->rewardable_id;
    }
    public function getReward2Attribute()
    {
        return $this->rewardable_id;
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if ($model->type === 'ware') {
                $model->rewardable_id = request('rewardable_id', $model->rewardable_id);
            } elseif ($model->type === 'vip') {
                $model->rewardable_id = request('rewardable_id2', $model->rewardable_id);
            } elseif ($model->type === 'badge') {
                $model->rewardable_id = request('rewardable_id3', $model->rewardable_id);
            } elseif ($model->type === 'achievement') {
                $model->rewardable_id = request('reward1', $model->reward1);
                // $model->reward =  $model->reward1;
            } elseif ($model->type === 'coins') {
                $model->reward = request('reward2', $model->reward);
            }
            unset($model->rewardable_id2);
            unset($model->rewardable_id3);
            unset($model->reward1);
            unset($model->reward2);
        });

        self::updating(function ($model) {
            if ($model->type === 'ware') {
                $model->rewardable_id = request('rewardable_id', $model->rewardable_id);
            } elseif ($model->type === 'vip') {
                $model->rewardable_id = request('rewardable_id2', $model->rewardable_id);
            } elseif ($model->type === 'badge') {
                $model->rewardable_id = request('rewardable_id3', $model->rewardable_id);
            } elseif ($model->type === 'achievement') {
                $model->reward =  $model->reward1;
            } elseif ($model->type === 'coins') {
                $model->reward = request('reward2', $model->reward2);
            }
            unset($model->rewardable_id2);
            unset($model->rewardable_id3);
            unset($model->reward1);
            unset($model->reward2);
        });
    }
}
