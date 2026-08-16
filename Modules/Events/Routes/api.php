<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Events\Http\Controllers\PkEventController;
use Modules\Events\Http\Controllers\WeeklyStarController;
use Modules\Events\Http\Controllers\EventPeriodController;
use Modules\Events\Http\Controllers\ChargeEventController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan' ,'update.last.seen'])->group(function () {
    Route::prefix('events')->middleware(['appFeatureEnable:weekly_star'])->group (function (){
        Route::get('top-weekly-events', [WeeklyStarController::class, 'topUsersEvent']);
        Route::get('role-event', [WeeklyStarController::class, 'roleEvent']);
        Route::get('top-details', [WeeklyStarController::class, 'topDetails']);
        Route::get('previous-event', [WeeklyStarController::class, 'previousWeeklyEvent']);

    });

    Route::prefix ('event-periods')->middleware(['appFeatureEnable:period_event'])->group (function (){
        Route::get('top-users', [EventPeriodController::class, 'topUsersEvent']);
        Route::get('details', [EventPeriodController::class, 'roleEvent']);
        Route::get('rewords', [EventPeriodController::class, 'topDetails']);
    });

    Route::prefix ('pk-events')->middleware(['appFeatureEnable:pk_event'])->group (function (){
        Route::post('top-pk-events', [PkEventController::class, 'topUsersPKEvent']);
        Route::get('top-details', [PkEventController::class, 'topDetails']);
        Route::get('pk-event', [PkEventController::class, 'pkEvent']);
    });

    Route::prefix ('charge-events')->middleware(['appFeatureEnable:target_events'])->group (function (){
        Route::get('role-events', [ChargeEventController::class, 'chargeEventRole']);
        Route::get('targets', [ChargeEventController::class, 'targets']);
        Route::post('received-rewards', [ChargeEventController::class, 'received_rewards']);
        Route::get('won_event', [ChargeEventController::class, 'wonEvent']);
        Route::get('leaderboard', [ChargeEventController::class, 'leaderboard']);
        Route::get('previous-winners', [ChargeEventController::class, 'previousWinners']);

    });

});
