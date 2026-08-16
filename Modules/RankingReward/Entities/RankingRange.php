<?php

namespace Modules\RankingReward\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingRange extends Model
{
    protected $fillable = ['ranking_type_id', 'min', 'max'];


    public function rewards()
    {
        return $this->hasMany(RankingReward::class, 'ranking_range_id');
    }

    public function rankingType(): BelongsTo
    {
        return $this->belongsTo(RankingType::class);
    }
}
