<?php

use Illuminate\Http\Request;
use Modules\RoomCup\Http\Controllers\Api\RoomCupController;

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

Route::group([
    'prefix' => 'room-cup',
    'middleware' => ['auth:sanctum', 'checkLatestToken', 'generalBan', 'localization', 'update.last.seen', 'room.cup']
], function () {
    Route::get('/report/{room_id}', [RoomCupController::class, 'myReward']);
    Route::get('/history/{room_id}', [RoomCupController::class, 'roomAdministratorManagement']);
    Route::get('cup-target', [RoomCupController::class, 'cupTargetHtml']);
});
