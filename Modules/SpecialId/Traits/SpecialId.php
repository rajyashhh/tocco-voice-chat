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
            $query->where('uuid', $toId)->orWhere(fn($q) => $q->where('special_id', $toId)->whereHas('specialId'));
        });
    }
    public function scopeLikeSearchByUuid(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like', '%' . $toId . '%')
                ->orWhere(function ($q) use ($toId) {
                    $q->where('special_id', 'like', '%' . $toId . '%')
                        ->whereHas('specialId');
                });
        });
    }

    public function scopeFitterByUuid(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like',  $toId . '%')->orWhere(fn($q) => $q->where('special_id', 'like',  $toId . '%')->whereHas('specialId'));
        });
    }

    public function scopeFitterByUuidUser(Builder $builder, $toId): Builder
    {
        return $builder->where(function ($query) use ($toId) {
            $query->where('uuid', 'like', '%' . $toId . '%')->orWhere(fn($q) => $q->where('special_id', 'like', '%' . $toId . '%')->whereHas('specialId'));
        });
    }

    public function soundEffect()
    {
        return $this->belongsTo(Pack::class, 'id', 'user_id')->where(function ($query) {
            $query->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->where("type", 21)->where('packs.is_used', 1)->where('get_type', 1);
    }
}
