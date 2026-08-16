<?php

namespace Modules\Achievement\Http\Traits;

use Modules\Achievement\Entities\GiftAchievement;

trait AchievementGift
{

    public function achievement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GiftAchievement::class, 'id', 'gift_id');
    }
}
