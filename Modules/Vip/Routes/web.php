<?php

use Modules\Vip\Http\Controllers\web\OVipController;
use Modules\Vip\Http\Controllers\web\OvipGiftTapController;
use Modules\Vip\Http\Controllers\web\VipPrivilegeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(
    [
        'prefix' => config('admin.route.prefix'),
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {

        Route::resource('vip_privilege', VipPrivilegeController::class);
        Route::resource('ovip', OVipController::class);
        Route::get('ovip-gift/{ovip_id}/{type?}', [OvipGiftTapController::class, 'index']);

        Route::get('ovip-settings', [OVipController::class, 'vipSettings']);
        Route::prefix('ware-gift')->group(function () {
            Route::get('/{level}/{type}', [OvipGiftTapController::class, 'create']);
            Route::post('/{level}', [OvipGiftTapController::class, 'store']);
        });
        Route::resource('ware-gifts', OvipGiftTapController::class);
        Route::prefix('ware-gifts')->group(function () {
            Route::get('/{id}/edit', [OvipGiftTapController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [OvipGiftTapController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [OvipGiftTapController::class, 'destroy'])->where('id', '[0-9]+');
        });
    }
);
