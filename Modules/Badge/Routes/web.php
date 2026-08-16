<?php

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

use Illuminate\Support\Facades\Route;
use Modules\Badge\Http\Controllers\web\BadgeController;
use Modules\Badge\Http\Controllers\web\UserBadgeController;
use Modules\Badge\Http\Controllers\web\DedicateBadgeController;
use Modules\Badge\Http\Controllers\web\MangerTypeBadgeController;



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
            'appFeatureEnable:achievement',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('badges', BadgeController::class);
        Route::resource('dedicate-badges', DedicateBadgeController::class);
        Route::get('user-badges', [UserBadgeController::class, 'index']);

        Route::prefix('manger-types/{manger_type_id}/badges')->group(function () {
            Route::get('/', [MangerTypeBadgeController::class, 'index']);
            Route::get('/create', [MangerTypeBadgeController::class, 'create'])->name('manger-type-badges.create');
            Route::post('/', [MangerTypeBadgeController::class, 'store']);
            Route::delete('/{id}', [MangerTypeBadgeController::class, 'destroy'])->where('id', '[0-9]+');
        });

    }
);
