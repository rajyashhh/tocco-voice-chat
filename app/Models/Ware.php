<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ware extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'wares';

    protected $guarded = [];

    protected $appends = ['image_type1', 'profile_frame_type'];

    protected $casts = [
        'key_json' => 'array',
    ];

    public function packs()
    {
        return $this->hasMany(Pack::class, 'target_id');
    }

    public function scopeIsNotUsedInPacks(Builder $query)
    {
        return $query->whereDoesntHave('packs', function ($q) {
            $q->where(fn ($q) => $q->where('packs.expire', 0)->orWhere('packs.expire', '>=', time()));
        });
    }

    public function scopeShowUserCustom(Builder $query, int $userId)
    {
        return $query->where(
            fn ($q) => $q->whereDoesntHave('ware_users')
                ->orWhereHas('ware_users', fn ($q) => $q->where('user_id', $userId))
        );
    }

    public function ware_users()
    {
        return $this->belongsToMany(User::class, 'user_ware', 'ware_id', 'user_id')->withPivot('disable');
    }

    public function getImageType1Attribute()
    {
        return $this->image_type;
    }

    public function getProfileFrameTypeAttribute()
    {
        return $this->image_type;
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if ($model->status) {
                unset($model->status);
            }
            unset($model->image_type1);
            unset($model->profile_frame_type);
        });

        self::updating(function ($model) {

            unset($model->image_type1);
            unset($model->profile_frame_type);
        });
        // Listen for the 'deleting' event of the Agency model
        self::deleting(function ($id) {

            $pack = Pack::where('target_id', $id->id)->delete();
        });
    }
}
