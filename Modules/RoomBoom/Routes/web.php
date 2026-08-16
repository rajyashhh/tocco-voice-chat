<?php

use Modules\RoomBoom\Http\Controllers\web\PercentageBoomController;
use Modules\RoomBoom\Http\Controllers\web\RoomBoomLevelController;
use Modules\RoomBoom\Http\Controllers\web\RoomBoomRewardController;
use Modules\RoomBoom\Http\Controllers\web\RoomBoomWinnerController;
use Modules\RoomBoom\Http\Controllers\web\SuperBoomRuleController;





Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'namespace'  => 'web',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('room_boom_levels', RoomBoomLevelController::class);
        Route::prefix('room_boom-theme')->group(function () {
            Route::get('/{level_id}', [RoomBoomLevelController::class, 'editBackgroundImage']);
            Route::put('/{level_id}', [RoomBoomLevelController::class, 'updateBackgroundImage']);
        });
        Route::resource('super-boom-rules', SuperBoomRuleController::class);
        Route::get('room_boom_winners', [RoomBoomWinnerController::class, 'index']);
        Route::get('/room-boom-settings', [PercentageBoomController::class, 'index']);
        Route::post('/room-boom-settings', [PercentageBoomController::class, 'save'])->name('room-boom-settings');
        Route::prefix('room_boom_rewards/{room_boom_level_id}')->group(function () {
            Route::get('/', [RoomBoomRewardController::class, 'index'])->name('room_boom_rewards.index');
            Route::get('/create', [RoomBoomRewardController::class, 'create'])->name('room_boom_rewards.create');
            Route::post('/', [RoomBoomRewardController::class, 'store'])->name('room_boom_rewards.store');
            Route::get('/{id}', [RoomBoomRewardController::class, 'show'])->where('id', '[0-9]+')->name('room_boom_rewards.show');
            Route::get('/{id}/edit', [RoomBoomRewardController::class, 'edit'])->where('id', '[0-9]+')->name('room_boom_rewards.edit');
            Route::put('/{id}', [RoomBoomRewardController::class, 'update'])->where('id', '[0-9]+')->name('room_boom_rewards.update');
            Route::delete('/{id}', [RoomBoomRewardController::class, 'destroy'])->where('id', '[0-9]+')->name('room_boom_rewards.destroy');
        });
    }
);
