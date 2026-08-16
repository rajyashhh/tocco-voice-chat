<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $fillable = ['name', 'code', 'direction', 'is_enabled'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($language) {
            Cache::forget("language_enabled_{$language->code}");
            Cache::put(
                'languages',
                self::where('is_enabled', true)
                    ->pluck('name', 'code')
                    ->toArray()
            );
        });

        static::deleted(function ($language) {
            Cache::forget("language_enabled_{$language->code}");
            Cache::put(
                'languages',
                self::where('is_enabled', true)
                    ->pluck('name', 'code')
                    ->toArray()
            );
        });
    }

    public static function getEnabledLanguages(): array
    {
        return Cache::rememberForever('languages', function () {
            return self::where('is_enabled', true)
                ->pluck('name', 'code')
                ->toArray();
        });
    }

    // public static function boot()
    // {
    //     parent::boot();

    //     self::saved(function () {
    //         Cache::put(
    //             'languages',
    //             self::where('is_enabled', true)
    //                 ->pluck('name', 'code')
    //                 ->toArray()
    //         );
    //     });

    //     self::deleted(function () {
    //         Cache::forget('languages');
    //     });
    // }
}
