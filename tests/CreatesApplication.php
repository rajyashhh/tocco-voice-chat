<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('CACHE_DRIVER=array');
        putenv('CACHE_STORE=array');
        $_ENV['CACHE_DRIVER'] = 'array';
        $_ENV['CACHE_STORE'] = 'array';
        $_SERVER['CACHE_DRIVER'] = 'array';
        $_SERVER['CACHE_STORE'] = 'array';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->booting(function () use ($app) {
            $app['config']->set('cache.default', 'array');
            $app['config']->set('cache.stores.redis.driver', 'array');
            \Illuminate\Support\Facades\Cache::store('array')->put('languages', ['en' => 'English', 'ar' => 'Arabic'], 86400);
            \Illuminate\Support\Facades\Cache::store('array')->put('settings.app_title_en', 'Tocco', 86400);
            \Illuminate\Support\Facades\Cache::store('array')->put('settings.app_title_ar', 'Tocco', 86400);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
