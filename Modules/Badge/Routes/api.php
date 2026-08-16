<?php

use Illuminate\Http\Request;

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


use Illuminate\Support\Facades\Route;
use Modules\Badge\Http\Controllers\BadgeController;
use Modules\Achievement\Http\Services\AchievementLevelsService;
Route::middleware(['auth:sanctum' ,'update.last.seen'])->group(function () {
    Route::get('/badges/users/{id}', [BadgeController::class,'index']);
});
