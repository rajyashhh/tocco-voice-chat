<?php

namespace App\Bd;

use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class BdServiceProvider extends ServiceProvider
{
    public function boot()
    {


        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/views', 'bd');
    }

    public function register()
    {
        //
    }
}
