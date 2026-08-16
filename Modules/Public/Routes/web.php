<?php

use Illuminate\Support\Facades\Route;
use Modules\Public\Http\Controllers\web\LevelIntervalController;
use Modules\Public\Http\Controllers\web\RewardLevelHistoryController;
use Modules\Public\Http\Controllers\web\RewardLevelIntervalController;

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
        Route::resource('level-intervals', LevelIntervalController::class);
        Route::resource('winner-level-intervals', RewardLevelHistoryController::class);
        Route::get('winner-level-intervals-rewards/{user_id}/{level_interval_id}', [RewardLevelHistoryController::class, 'getRewards']);

        Route::prefix('reward_level_interval/{level_interval_id}')->group(function () {
            Route::get('/', [RewardLevelIntervalController::class, 'index']);
            Route::get('/create', [RewardLevelIntervalController::class, 'create']);
            Route::post('/', [RewardLevelIntervalController::class, 'store']);
            Route::get('/{id}', [RewardLevelIntervalController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [RewardLevelIntervalController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [RewardLevelIntervalController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [RewardLevelIntervalController::class, 'destroy'])->where('id', '[0-9]+');
        });
    }
);
