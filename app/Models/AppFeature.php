<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppFeature extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($feature) {
            Cache::forget("app_feature_status_{$feature->slug}");
        });

        static::deleted(function ($feature) {
            Cache::forget("app_feature_status_{$feature->slug}");
        });
    }
}
