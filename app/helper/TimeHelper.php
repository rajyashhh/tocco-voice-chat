<?php

namespace App\helper;

use Carbon\Carbon;
use DateTimeZone;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
class TimeHelper
{

    public static function weekStart()
    {
        // Hermetic build: rememberForever opens a cache (Redis) connection to
        // read the key BEFORE the closure runs, so it throws during image build
        // (package:discover / view:cache) when no Redis is up — the Schema guard
        // inside the closure never gets a chance to run. Catch the connection
        // failure and fall back to the default; runtime always has the stack.
        try {
            return cache()->rememberForever('week_start', function () {
                // Cold-boot safety: settings table is absent before migrations.
                if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    return 'MONDAY';
                }
                return Setting::where('key', 'week_start')->value('value') ?? 'MONDAY';
            });
        } catch (\Throwable $e) {
            return 'MONDAY';
        }
    }

    public static function weekEnd()
    {
        try {
            return cache()->rememberForever('week_end', function () {
                // Cold-boot safety: settings table is absent before migrations.
                if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    return 'SUNDAY';
                }
                return Setting::where('key', 'week_end')->value('value') ?? 'SUNDAY';
            });
        } catch (\Throwable $e) {
            return 'SUNDAY';
        }
    }
    
    public static function startOfWeekConst()
    {
        return constant("Carbon\\Carbon::" . strtoupper(self::weekStart()));
    }
    
    public static function endOfWeekConst()
    {
        return constant("Carbon\\Carbon::" . strtoupper(self::weekEnd()));
    }

  
    public static function clearCache(): void
    {
        Cache::forget('timezone');
        Cache::forget('week_start');
        Cache::forget('week_end');
    }
 
}
