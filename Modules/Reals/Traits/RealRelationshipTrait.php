<?php

namespace Modules\Reals\Traits;

use Modules\Reals\Entities\{Real, RealUserComment, RealUserLike, ReelsUserSetting};

trait RealRelationshipTrait
{
    public function real_likes()
    {
        return $this->hasMany(RealUserLike::class);
    }

    public function real_comments()
    {
        return $this->hasMany(RealUserComment::class);
    }

    public function reals()
    {
        return $this->hasMany(Real::class);
    }

    public function reelSetting()
    {
        return $this->hasOne(ReelsUserSetting::class, 'user_id');
    }

    public function getRealTypeAttribute()
    {
        $realSetting = $this->getRealSetting();


        return $realSetting->all_unique_value;
    }

    public function setRealTypeAttribute($value): void
    {
        $realSetting = $this->getRealSetting();
        $realSetting->update([
                                 'all_unique_value' => $value
                             ]);
        $this->setRelation('reelSetting', $realSetting);
    }

    public function getFollowingUniqueValueAttribute()
    {
        $realSetting = $this->getRealSetting();


        return $realSetting->following_unique_value;
    }

    public function setFollowingUniqueValueAttribute($value): void
    {
        $realSetting = $this->getRealSetting();
        $realSetting->update([
                                 'following_unique_value' => $value
                             ]);
        $this->setRelation('reelSetting', $realSetting);
    }

    public function getLastFollowingReelIdAttribute()
    {
        $realSetting = $this->getRealSetting();


        return $realSetting->last_following_reel_id;
    }

    public function setLastFollowingReelIdAttribute($value): void
    {
        $realSetting = $this->getRealSetting();
        $realSetting->update([
                                 'last_following_reel_id' => $value
                             ]);
        $this->setRelation('reelSetting', $realSetting);
    }

    public function getLastAllReelIdAttribute()
    {
        $realSetting = $this->getRealSetting();


        return $realSetting->last_all_reel_id;
    }

    public function setLastAllReelIdAttribute($value): void
    {
        $realSetting = $this->getRealSetting();
        $realSetting->update([
                                 'last_all_reel_id' => $value
                             ]);
        $this->setRelation('reelSetting', $realSetting);
    }
    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\BelongsTo|mixed
     */
    public function getRealSetting(): mixed
    {
        if ((!$this->reelSetting)) {
            $model = $this->reelSetting()->createOrFirst(['user_id' => $this->id]);


            return $model;
        } else {
            return $this->reelSetting;
        }
    }
}
