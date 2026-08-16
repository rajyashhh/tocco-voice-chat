<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $appends = ['publish'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeWhereIsNotSeen(Builder $query, $utcTimestamp): Builder
    {
        return $query->whereDate('publish_at', '>', Carbon::createFromTimestamp($utcTimestamp, 'utc'));
    }

    public function getPublishAttribute()
    {
        return $this->publish_at === null ? 0 : 1;
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (request('publish') === 'on') {
                $model->publish_at = now();
            } else {
                $model->publish_at = null;
            }
            unset($model->publish);
        });

        self::updating(function ($model) {
            if (request('publish') === 'on') {

                $model->publish_at = now();
            } else {
                $model->publish_at = null;
            }
            unset($model->publish);
        });
    }
}
