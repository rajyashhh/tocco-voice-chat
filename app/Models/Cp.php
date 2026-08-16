<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\CP\Entities\CpLevel;
use Modules\CP\Entities\CpRelation;

class Cp extends Model
{
    use TimestampsWithTimezone;

    protected $guarded = [];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function level()
    {
        return $this->belongsTo(CpLevel::class, 'level_id');
    }

    public function toUser()
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

    protected static function booted()
    {
        static::created(function ($cp) {

        });
    }
}
