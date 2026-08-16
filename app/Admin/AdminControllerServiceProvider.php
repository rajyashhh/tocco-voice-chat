<?php

namespace App\Admin\Controllers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AdminControllerServiceProvider extends ServiceProvider
{
    public function boot()
    {

        if (!$this->app->routesAreCached()) {
            $this->mapBdRoutes();
        }
    }

    protected function mapBdRoutes()
    {
        Route::middleware(config('admin.route.middleware'))
            ->prefix(config('admin.route.prefix')) // عادةً "admin"
            ->namespace('App\\Bd\\Controllers')
            ->group(base_path('app/Bd/routes.php'));
    }
}
