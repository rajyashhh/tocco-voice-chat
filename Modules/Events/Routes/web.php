<?php

use Modules\Events\Http\Controllers\web\PkEventController;
use Modules\Events\Http\Controllers\web\PkEventGiftController;
use Modules\Events\Http\Controllers\web\TargetEventController;
use Modules\Events\Http\Controllers\web\RewardTargetController;
use Modules\Events\Http\Controllers\web\WeeklyEventGiftNController;
use Modules\Events\Http\Controllers\web\ChargeKingController;
use Modules\Events\Http\Controllers\web\ChargeKingRewardController;
use Modules\Events\Http\Controllers\web\ChargeKingPageController;
use Modules\Events\Http\Controllers\web\WeeklyStarPageController;
use Modules\Events\Http\Controllers\web\PkEventPageController;
use Illuminate\Support\Facades\Route;

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
        Route::resource('event-period', 'EventPeriodController');
        Route::resource('charge-king', ChargeKingController::class);
        Route::prefix('charge-king-rewards/{charge_king_id}')->group(function () {
            Route::get('/', [ChargeKingRewardController::class, 'index']);
            Route::get('/{level}/create', [ChargeKingRewardController::class, 'create']);
            Route::post('/{level}', [ChargeKingRewardController::class, 'store']);
            Route::get('/{id}', [ChargeKingRewardController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [ChargeKingRewardController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [ChargeKingRewardController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [ChargeKingRewardController::class, 'destroy'])->where('id', '[0-9]+');
        });
        Route::resource('weekly-events-new', 'WeeklyEventNController');
        Route::resource('target-events', TargetEventController::class);
        Route::resource('pk-events', PkEventController::class);
        Route::prefix('weekly-events-gift/{weekly_event_id}')->group(function () {
            Route::get('/', [WeeklyEventGiftNController::class, 'index']);
            Route::get('/{level}/create', [WeeklyEventGiftNController::class, 'create']);
            Route::post('/{level}', [WeeklyEventGiftNController::class, 'store']);
            Route::get('/{id}', [WeeklyEventGiftNController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [WeeklyEventGiftNController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [WeeklyEventGiftNController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [WeeklyEventGiftNController::class, 'destroy'])->where('id', '[0-9]+');
        });

        Route::prefix('pk-events-gift/{pk_type}/{pk_event_id}')->group(function () {
            Route::get('/', [PkEventGiftController::class, 'index']);
            Route::get('/{level}/create', [PkEventGiftController::class, 'create']);
            Route::post('/{level}', [PkEventGiftController::class, 'store']);
            Route::get('/{id}', [PkEventGiftController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [PkEventGiftController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [PkEventGiftController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [PkEventGiftController::class, 'destroy'])->where('id', '[0-9]+');
        });

    

        Route::delete('target-events-gift/{id}/{targets}', [RewardTargetController::class, 'destroyBulk'])
        ->where('targets', '.*');
        Route::prefix('target-events-gift/{charge_event_id}')->group(function () {
            Route::get('/', [RewardTargetController::class, 'index']);
            Route::get('/create', [RewardTargetController::class, 'create']);
            Route::post('/', [RewardTargetController::class, 'store']);
            Route::get('/{id}', [RewardTargetController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/edit', [RewardTargetController::class, 'edit'])->where('id', '[0-9]+');
            Route::put('/{id}', [RewardTargetController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/{id}', [RewardTargetController::class, 'destroy'])->where('id', '[0-9]+');
        });

        Route::resource('general-rols', 'GeneralRoleController');
        Route::resource('event-reports', 'EventReportController');
        Route::post("update-weekly-star", function () {
            \Modules\Events\Entities\WeeklyStar::whereNull('type')->update([
                'type' => "weekly_star"
            ]);
        });
    }
);

Route::get('charge-king-view', [ChargeKingPageController::class, 'view'])->middleware('throttle:40,1');
Route::get('weekly-star-view', [WeeklyStarPageController::class, 'view'])->middleware('throttle:40,1');
Route::get('pk-event-view', [PkEventPageController::class, 'view'])->middleware('throttle:40,1');
