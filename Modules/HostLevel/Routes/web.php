<?php

use Modules\HostLevel\Http\Controllers\web\HostLevelController;
use Modules\HostLevel\Http\Controllers\web\HostLevelRewardController;
use Modules\HostLevel\Http\Controllers\web\HostLevelSettingController;

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
            'host.level.action',
            //            'adminGeneralBan',
            'multiLanguage',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('host-levels', HostLevelController::class);
        Route::resource('host-level-settings', HostLevelSettingController::class);
        Route::post('host-level-settings/save', [HostLevelSettingController::class, 'save'])->name('host-level-settings.save');

        Route::prefix('host-level-reward/{host_level_id}')->group(function () {
            Route::get('/', [HostLevelRewardController::class, 'index']);
            Route::get('/create', [HostLevelRewardController::class, 'create']);
            Route::post('/', [HostLevelRewardController::class, 'store']);
            Route::get('/{id}', [HostLevelRewardController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [HostLevelRewardController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [HostLevelRewardController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [HostLevelRewardController::class, 'destroy'])->where('id', '[0-9]+');
        });
    }

);
