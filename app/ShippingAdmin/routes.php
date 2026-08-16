<?php

use App\ShippingAdmin\Controllers\AuthController;
use App\ShippingAdmin\Controllers\CountryManagerFundingController;
use App\ShippingAdmin\Controllers\HomeController;
use App\ShippingAdmin\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use KevinSoft\MultiLanguage\MultiLanguage;

/*
| Shipping Super Admin portal. Mirrors app/Bd/routes.php: a standalone
| laravel-admin gate over admin_users, distinguished by
| type=shipping_super_admin and served under the /shippingAdmin prefix.
*/

// Public auth routes (login page + credential post).
Route::group(
    [
        'prefix' => 'shippingAdmin',
        'middleware' => ['web', 'multiLanguage'],
        'as' => 'shippingAdmin.',
    ],
    function () {
        if (MultiLanguage::config('show-login-page', true)) {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        }
        Route::post('login', [AuthController::class, 'postLogin']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('locale', [AuthController::class, 'locale']);
    }
);

// Authenticated portal routes.
Route::group(
    [
        'prefix' => 'shippingAdmin',
        'namespace' => 'App\\ShippingAdmin\\Controllers',
        'middleware' => [
            'web',
            'admin.auth',
            'portal.type:shipping_super_admin',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            'multiLanguage',
        ],
        'as' => 'shippingAdmin.',
    ],
    function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('setting', [AuthController::class, 'getSetting']);
        Route::put('update-setting', [AuthController::class, 'putSetting']);

        Route::get('wallet', [WalletController::class, 'index'])->name('wallet');
        Route::post('wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');
    }
);

/*
| Country Manager -> Shipping Super Admin funding. Mounted under the existing
| /superadmin (country manager) portal so it inherits that gate's auth stack.
| The handler lives in the shipping layer because it feeds it.
*/
Route::group(
    [
        'prefix' => 'superadmin',
        'middleware' => [
            'web',
            'admin.auth',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            'multiLanguage',
        ],
        'as' => 'superadmin.shipping.',
    ],
    function () {
        Route::post('shipping-admin/fund', [CountryManagerFundingController::class, 'fund'])->name('fund');
    }
);