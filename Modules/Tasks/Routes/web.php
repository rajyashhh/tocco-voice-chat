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
use Modules\Tasks\Http\Controllers\DayController;
//use Modules\Tasks\Http\Controllers\DayController;
use Modules\Tasks\Http\Controllers\DailyTaskController;
use Modules\Tasks\Http\Controllers\TaskProgressController;
use Modules\Tasks\Http\Controllers\UserDayProgressController;
use Modules\Tasks\Http\Controllers\UserDayTaskProgressController;
use Modules\Tasks\Http\Controllers\TaskRewardController;
use Modules\Tasks\Http\Controllers\UserTaskRewardController;


Route::group(
    [
        'prefix' => config('admin.route.prefix'),
        //'namespace' => 'web',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
            'appFeatureEnable:achievement',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('days', DayController::class);
        Route::prefix('{day_id}/day-tasks')->group(function () {
            Route::get('/', [DailyTaskController::class, 'index'])->name('day-tasks.index');
            Route::get('/create', [DailyTaskController::class, 'create'])->name('day-tasks.create');
            Route::post('/', [DailyTaskController::class, 'store'])->name('day-tasks.store');
            Route::get('/{id}', [DailyTaskController::class, 'show'])->name('day-tasks.show');
            Route::get('/{id}/edit', [DailyTaskController::class, 'edit'])->name('day-tasks.edit');
            Route::put('/{id}', [DailyTaskController::class, 'update'])->name('day-tasks.update');
            Route::delete('/{id}', [DailyTaskController::class, 'destroy'])->name('day-tasks.destroy');
        });

        Route::prefix('{day_id}/day-rewards')->group(function () {
            Route::get('/', [TaskRewardController::class, 'index'])->name('day-rewards.index');
            Route::get('/create', [TaskRewardController::class, 'create'])->name('day-rewards.create');
            Route::post('/', [TaskRewardController::class, 'store'])->name('day-rewards.store');
            Route::get('/{id}', [TaskRewardController::class, 'show'])->name('day-rewards.show');
            Route::get('/{id}/edit', [TaskRewardController::class, 'edit'])->name('day-rewards.edit');
            Route::put('/{id}', [TaskRewardController::class, 'update'])->name('day-rewards.update');
            Route::delete('/{id}', [TaskRewardController::class, 'destroy'])->name('day-rewards.destroy');
        });
    }
);
