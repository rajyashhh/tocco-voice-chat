<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfigObserver
{
    public function saved(Config $config): void
    {
        $this->refreshCache($config);
    }

    public function deleted(Config $config): void
    {
        $this->refreshCache($config);
    }

    protected function refreshCache(Config $config): void
    {
        Cache::forget('all_configs');
        Cache::forget($config->name);

        foreach (['en', 'ar'] as $code) {
            Cache::forget("badges_{$code}");
        }

        Cache::forget('max_message');
        Cache::forget('rooms_make_rooms_top');

        CacheHelper::cacheConfig();
    }
}
