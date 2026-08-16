<?php

use Modules\Milestones\Http\Controllers\web\MilestoneController;
use Modules\Milestones\Http\Controllers\web\MilestoneRewardController;


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

Route::prefix('milestones')->group(function () {
    Route::get('/', [MilestoneController::class, 'index']);
});


Route::group(
    [
        'prefix' => config('admin.route.prefix'),
        'namespace' => 'web',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('milestones', MilestoneController::class);
        Route::get('milestones/{id}/sync', [MilestoneController::class, 'syncMilestone'])->name('milestones.sync');
        Route::prefix('milestone-rewards/{milestone_id}')->group(function () {
            Route::get('/', [MilestoneRewardController::class, 'index']);
            Route::get('/create', [MilestoneRewardController::class, 'create'])->name('milestone-rewards.create');
            Route::post('/', [MilestoneRewardController::class, 'store']);
            Route::get('/{id}', [MilestoneRewardController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [MilestoneRewardController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [MilestoneRewardController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [MilestoneRewardController::class, 'destroy'])->where('id', '[0-9]+');
        });

    }
);


