<?php

use Modules\SpecialId\Http\Controllers\web\SpecialWareController;
use Modules\SpecialId\Http\Controllers\web\SpecialHistoryController;
use Modules\SwitchAccount\Http\Controllers\web\UsersDevicesHistoriesController;
use Illuminate\Support\Facades\Route;

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
        'prefix'     => config('admin.route.prefix'),
        'namespace'  => 'web',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('user-devices-histories', UsersDevicesHistoriesController::class);
    }
);
