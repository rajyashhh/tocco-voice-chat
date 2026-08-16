<?php

namespace App\Traits;

trait MomentRelationshipTrait
{
    public function moment_likes(){
        return $this->hasMany(\Modules\Moment\Entities\MomentLikes::class);
    }

    public function moment_comments(){
        return $this->hasMany(\Modules\Moment\Entities\MomentCommint::class);
    }

    public function moments(){
        return $this->hasMany(\Modules\Moment\Entities\Moment::class);
    }
}
