<?php

namespace App\Providers;

use App\Broadcasting\Centrifugo\ChannelMapper;
use App\Broadcasting\CentrifugoBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register the Centrifugo broadcaster.
     *
     * Centrifugo is now the only realtime broadcast transport. The former
     * Pusher / dual / switchable drivers were removed together with the
     * Pusher provider, so there is nothing left to switch between.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(BroadcastManager::class, function (BroadcastManager $manager) {
            $manager->extend('centrifugo', function ($app, $config) {
                return new CentrifugoBroadcaster($config, new ChannelMapper());
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Centrifugo is the ONLY realtime transport. Force it for any non-centrifugo
        // default (a missing/legacy/removed/misconfigured BROADCAST_DRIVER such as a
        // stale 'pusher' env would otherwise throw "connection not defined" at boot,
        // or silently drop realtime via the inert null/log driver). The ONLY value
        // left untouched is an intentional inert null/log in console/tests, so artisan
        // commands and the test suite never publish to a live transport.
        $default = config('broadcasting.default');
        $keepInertInConsole = $this->app->runningInConsole()
            && in_array($default, ['null', 'log', '', null], true);

        if ($default !== 'centrifugo' && ! $keepInertInConsole) {
            config(['broadcasting.default' => 'centrifugo']);
        }

        Broadcast::routes(['middleware' => ['auth:sanctum']]);
        require base_path('routes/channels.php');
    }
}
