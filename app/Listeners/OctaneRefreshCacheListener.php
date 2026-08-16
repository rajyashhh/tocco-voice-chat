<?php

namespace App\Listeners;

// Avoid hard type dependency if Octane classes are not autoloaded at analysis time
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class OctaneRefreshCacheListener
{
    /**
     * Handle Octane TickReceived events to flush in-memory cache when a signal file exists.
     */
    public function handle($event): void
    {
        $triggerFile = storage_path('framework/cache_flush_signal');

        if (File::exists($triggerFile)) {
            Cache::store('octane')->clear();
            File::delete($triggerFile);
            logger('Octane Cache Flushed via Signal File.');
        }
    }
}
