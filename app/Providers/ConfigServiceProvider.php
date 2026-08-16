<?php

namespace App\Providers;

use App\Helpers\CacheHelper;
use App\Helpers\Common;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Hermetic build: warming these caches opens DB + cache (Redis)
        // connections during boot, which package:discover / view:cache trigger
        // at image-build time when no services are up (getaddrinfo for "redis"
        // fails, DB refused). Guard so a clean build never depends on a running
        // stack; at runtime the services are always present and this behaves
        // exactly as before (the values are re-warmed on the first real request).
        try {
            Config::set('exp_percentages', $this->getReceivedAndSanderPercentage());
            CacheHelper::cacheConfig();
        } catch (\Throwable $e) {
            // no services during build — skip warm-up, runtime will populate.
        }

        $requestPath = \Request::path();

        if (\Str::startsWith($requestPath, 'preview')) { //admin.route.prefix,admin.auth.controller
            Config::set('session.cookie', 'laravel_preview');
            Config::set('admin.route.prefix', 'preview/admin');
            $get = Config::get('admin.route.middleware');
            if (!in_array('prevent-delete', $get, true)) {
                $get[] = "prevent-delete";
            }
            Config::set('admin.route.middleware', $get);
            Config::set('admin.auth.controller', \App\Admin\Controllers\Preview\AuthController::class);
        } else {
            // 🔴 Octane safety: this provider mutates GLOBAL config for /preview
            // requests, but Octane workers are long-lived — without an explicit reset
            // a worker that served a /preview request keeps `session.cookie =
            // laravel_preview` (+ preview prefix/controller) and then serves /admin
            // requests on the PREVIEW session, so the real admin's tab silently flips
            // to the preview (view-only) user. Reset every preview override back to its
            // default on every non-preview request so the leak can't cross requests.
            Config::set('session.cookie', \Str::slug(env('APP_NAME', 'laravel'), '_') . '_session');
            Config::set('admin.route.prefix', env('ADMIN_ROUTE_PREFIX', 'admin'));
            Config::set('admin.auth.controller', \App\Admin\Controllers\AuthController::class);
            $get = Config::get('admin.route.middleware');
            if (is_array($get) && in_array('prevent-delete', $get, true)) {
                Config::set('admin.route.middleware', array_values(array_diff($get, ['prevent-delete'])));
            }
        }

        // Timezone value — getTimezone() warms a Cache::rememberForever entry,
        // so it opens a Redis connection at boot. Guard it for the same hermetic
        // build reason as the warm-up block above; runtime always has the stack.
        try {
            Config::set('app.owner_timezone', getTimezone());
        } catch (\Throwable $e) {
            Config::set('app.owner_timezone', 'UTC');
        }
    }

    public function getReceivedAndSanderPercentage(): array
    {
        return Cache::remember('exp_percentages', now()->addMinutes(60), function () {
            $keys = [
                'exp_sender_percentage',
                'exp_received_percentage',
                'exp_cp_percentage',
                'exp_room_percentage',
                'exp_charge_percentage'
            ];

            $collection = Common::getConfFromKey($keys);
            $values = [];

            foreach ($keys as $key) {
                $config = $collection->where('name', $key)->first();
                $values[$key] = $config ? $config->value  : 1;
            }

            return $values;
        });
    }
}
