<?php

use Modules\RoleRewards\Http\Controllers\web\RoleRewardsController;
use Modules\RoleRewards\Http\Controllers\web\UserHistoryRewardController;

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


        Route::prefix('role-rewards/{role_id}')->group(function () {
            Route::get('/', [RoleRewardsController::class, 'index']);
            Route::get('/create', [RoleRewardsController::class, 'create'])->name('role-rewards.create');
            Route::post('/', [RoleRewardsController::class, 'store']);
            Route::get('/{id}', [RoleRewardsController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [RoleRewardsController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [RoleRewardsController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [RoleRewardsController::class, 'destroy'])->where('id', '[0-9]+');
        });

        Route::resource('user-history-rewards', UserHistoryRewardController::class);

    }
);
