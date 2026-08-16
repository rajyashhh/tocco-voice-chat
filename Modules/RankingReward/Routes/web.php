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

use Modules\RankingReward\Http\Controllers\RankingRewardController;
use Modules\RankingReward\Http\Controllers\RankingTypeController;
use Modules\RankingReward\Http\Controllers\WinnerRankingController;

Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('ranking-types', RankingTypeController::class);
        Route::resource('winner-rankings', WinnerRankingController::class);
       Route::get('rewards/{id}', [RankingTypeController::class, 'getRewards']);

        Route::prefix('ranking-rewards/{ranking_range_id}')->group(function () {
//            Route::get('/', [RankingRewardController::class, 'index'])->name('ranking_rewards.index');
//            Route::get('/create', [RankingRewardController::class, 'create'])->name('ranking_rewards.create');
//            Route::post('/', [RankingRewardController::class, 'store'])->name('ranking_rewards.store');
//            Route::get('/{id}', [RankingRewardController::class, 'show'])->where('id', '[0-9]+')->name('ranking_rewards.show');
//            Route::get('/{id}/edit', [RankingRewardController::class, 'edit'])->where('id', '[0-9]+')->name('ranking_rewards.edit');
//            Route::put('/{id}', [RankingRewardController::class, 'update'])->where('id', '[0-9]+')->name('ranking_rewards.update');
            Route::delete('/{id}', [RankingRewardController::class, 'destroy'])->where('id', '[0-9]+')->name('ranking_rewards.destroy');
        });
    }
);
