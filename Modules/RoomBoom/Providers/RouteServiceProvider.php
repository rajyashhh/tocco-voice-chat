<?php

namespace Modules\RoomBoom\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\RoomBoom\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        // No 'room.boom' feature-flag here: every web route of this module is
        // an ADMIN page (see Routes/web.php — admin prefix + admin middleware).
        // The flag must gate the mobile API only (Routes/api.php); gating the
        // panel too meant admins got 403 on the management screens and could
        // never configure levels/rewards while the feature was switched off.
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('RoomBoom', '/Routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('RoomBoom', '/Routes/api.php'));
    }
}
