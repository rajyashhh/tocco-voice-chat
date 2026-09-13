<?php

namespace Modules\SpecialId\Traits;

use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;

trait SpecialId
{

    public function specialId()
    {
        return $this->belongsTo(Pack::class, 'id', 'user_id')->where(function ($query) {
            $query->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->where("type", 25)->where('packs.is_used', 1);
    }

    public function scopeSearchByUuid(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', $toId)->orWhereHas('specialId', fn($q) => $q->where('packs.target_id', $toId));
        });
    }
    public function scopeLikeSearchByUuid(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like', '%' . $toId . '%')
                ->orWhereHas('specialId', fn($q) => $q->where('packs.target_id', 'like', '%' . $toId . '%'));
        });
    }

    public function scopeFitterByUuid(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like',  $toId . '%')->orWhereHas('specialId', fn($q) => $q->where('packs.target_id', 'like',  $toId . '%'));
        });
    }

    public function scopeFitterByUuidUser(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like', '%' . $toId . '%')->orWhereHas('specialId', fn($q) => $q->where('packs.target_id', 'like', '%' . $toId . '%'));
        });
    }

    public function soundEffect()
    {
        return $this->belongsTo(Pack::class, 'id', 'user_id')->where(function ($query) {
            $query->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->where("type", 21)->where('packs.is_used', 1)->where('get_type', 1);
    }
}
