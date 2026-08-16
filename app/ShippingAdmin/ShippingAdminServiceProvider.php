<?php

namespace App\ShippingAdmin;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the Shipping Super Admin gate. Mirrors App\Bd\BdServiceProvider: a
 * standalone laravel-admin portal over admin_users, distinguished by
 * type=shipping_super_admin and served under the /shippingAdmin prefix.
 */
class ShippingAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'shippingAdmin');
    }

    public function register(): void
    {
        //
    }
}