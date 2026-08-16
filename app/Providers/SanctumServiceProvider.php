<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Events\TokenAuthenticated;
use App\Listeners\UpdateTokenLastUsedAt;

class SanctumServiceProvider extends ServiceProvider
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
        // Listen to TokenAuthenticated event and update last_used_at safely
        $this->app['events']->listen(
            TokenAuthenticated::class,
            UpdateTokenLastUsedAt::class
        );
    }
}
