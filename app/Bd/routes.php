<?php

use App\Bd\Controllers\AgencyController;
use App\Bd\Controllers\AuthController;
use App\Bd\Controllers\BdSalariesController;
use App\Bd\Controllers\ChargeController;
use App\Bd\Controllers\HomeController;
use App\Bd\Controllers\MultiLanguageController;
use App\Bd\Controllers\RequestAgencyController;
use App\Bd\Controllers\UserController;
use App\Bd\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use KevinSoft\MultiLanguage\MultiLanguage;
use Modules\Form\Http\Controllers\FormRequestController;




Route::prefix('bd')->name('bd.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Route::middleware(['auth:bd'])->group(function () {
    //     Route::get('/', [\App\Bd\Controllers\DashboardController::class, 'index'])->name('dashboard');
    // });
});

Route::group(
    [
        'prefix' => 'bd',
        'namespace' => '',
        'middleware' => [
            'web',
            'multiLanguage',
        ],
        'as' => 'bd.',
    ],
    function () {
        if (MultiLanguage::config("show-login-page", true)) {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        }
        Route::post('login', [AuthController::class, 'postLogin']);
        Route::get('logout', [AuthController::class, 'logout']);



    }
);

Route::group(
    [
        'prefix' => 'bd',
        'namespace' => 'App\\Bd\\Controllers',
        'middleware' => [
            'web',
            'admin.auth',
            'portal.type:bd',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            // 'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
        ],
        'as' => 'bd.',
    ],
    function () {
        Route::get('setting', [AuthController::class, 'getSetting']);
        Route::put('update-setting', [AuthController::class, 'putSetting']);

        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/charges', [ChargeController::class, 'index'])->name('charges');
        Route::resource('/agencies', AgencyController::class);
        Route::resource('/salaries', BdSalariesController::class);
        Route::resource('/charges', ChargeController::class);
        // Route::resource('/wallet', 'WalletController');
        Route::post('wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');
        Route::post('salary/transfer', [WalletController::class, 'transfer'])->name('salary.transfer');
        Route::get('agencies/profile/{id}', [AgencyController::class, 'profile'])->name('agency.profile');
        Route::get('users/profile/{id}', [UserController::class, 'show'])->name('user.profile');

        Route::post('/locale', MultiLanguageController::class . '@locale');

        Route::resource('/request-agencies', RequestAgencyController::class);


        Route::prefix('requests')->group(function () {
            Route::post('{id}/approve', [FormRequestController::class, 'approve'])->name('requests.approve');
            Route::post('{id}/reject', [FormRequestController::class, 'reject'])->name('requests.reject');
        });
    }
);
