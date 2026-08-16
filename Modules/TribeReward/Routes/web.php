<?php

use Modules\TribeReward\Http\Controllers\web\TribePeriodController;
use Modules\TribeReward\Http\Controllers\web\TribeRewardController;
use Modules\TribeReward\Http\Controllers\web\TribeTopController;

Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('tribe_periods', TribePeriodController::class);

        Route::prefix('tribe_tops/{tribe_period_id}')->group(function () {
            Route::get('/', [TribeTopController::class, 'index'])->name('tribe_tops.index');
            Route::get('/create', [TribeTopController::class, 'create'])->name('tribe_tops.create');
            Route::post('/', [TribeTopController::class, 'store'])->name('tribe_tops.store');
            Route::get('/{id}', [TribeTopController::class, 'show'])->where('id', '[0-9]+')->name('tribe_tops.show');
            Route::get('/{id}/edit', [TribeTopController::class, 'edit'])->where('id', '[0-9]+')->name('tribe_tops.edit');
            Route::put('/{id}', [TribeTopController::class, 'update'])->where('id', '[0-9]+')->name('tribe_tops.update');
            Route::delete('/{id}', [TribeTopController::class, 'destroy'])->where('id', '[0-9]+')->name('tribe_tops.destroy');
        });

        Route::prefix('tribe_rewards/{tribe_top_id}')->group(function () {
            Route::get('/', [TribeRewardController::class, 'index'])->name('tribe_rewards.index');
            Route::get('/create', [TribeRewardController::class, 'create'])->name('tribe_rewards.create');
            Route::post('/', [TribeRewardController::class, 'store'])->name('tribe_rewards.store');
            Route::get('/{id}', [TribeRewardController::class, 'show'])->where('id', '[0-9]+')->name('tribe_rewards.show');
            Route::get('/{id}/edit', [TribeRewardController::class, 'edit'])->where('id', '[0-9]+')->name('tribe_rewards.edit');
            Route::put('/{id}', [TribeRewardController::class, 'update'])->where('id', '[0-9]+')->name('tribe_rewards.update');
            Route::delete('/{id}', [TribeRewardController::class, 'destroy'])->where('id', '[0-9]+')->name('tribe_rewards.destroy');
        });
    }
);
