<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use App\Helpers\CacheHelper;

class RefreshCacheListener
{
    /**
     */
    public function handle($event): void
    {
        Cache::forget('all_configs');
        Cache::forget('all_settings');
        Cache::forget('pusher_config'); // Refresh Pusher config
        
        CacheHelper::cacheConfig();
        CacheHelper::cacheSettings();
    }
}
