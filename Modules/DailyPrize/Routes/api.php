<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\DailyPrize\Http\Controllers\Api\DailyGiftController;

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

Route::middleware(['auth:sanctum', 'localization' ,'update.last.seen'])->group(function () {
    Route::get('current-day',[DailyGiftController::class,'current_day']);
    Route::post('receive-daily-prize',[DailyGiftController::class,'receive_daily_prize']);
});
