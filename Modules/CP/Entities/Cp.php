<?php

namespace Modules\CP\Entities;

use App\Helpers\Common;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Cp extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function cpRelation()
    {
        return $this->belongsTo(CpRelation::class, 'cp_relation_id');
    }

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function relation()
    {
        return $this->belongsTo(CpRelation::class, 'cp_relation_id');
    }

    public function scopeRelation($query)
    {
        return $query->whereHas('relation', function ($query) {
            $query->where('type', 'lovely');
        });
    }

    public function getPartnerAttribute()
    {
        return $this->user_one_id == auth()->id()
            ?  $this->toUser : $this->fromUser;
    }

    public function level()
    {
        return $this->belongsTo(CpLevel::class, 'level_id');
    }

    public function scopeRelationType($query, $type)
    {
        return $query->whereHas('relation', function ($query) use ($type) {
            $query->where('type', $type);
        });
    }


    protected static function booted()
    {
        static::created(function ($cp) {});
    }
}
