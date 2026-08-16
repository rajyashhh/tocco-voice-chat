<?php

use Modules\RoomBoom\Http\Controllers\Api\RoomBoomLevelController;
use Modules\RoomBoom\Http\Controllers\Api\SuperBoomRuleController;

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
    'prefix' => 'boom_levels',
    'middleware' => ['auth:sanctum', 'checkLatestToken', 'generalBan', 'room.boom','localization', 'update.last.seen']
], function () {
    Route::get('get_videos', [RoomBoomLevelController::class, 'getVideos']);
    Route::get('{id}', [RoomBoomLevelController::class, 'index']);
});
    
Route::get('room-boom/themes', [RoomBoomLevelController::class, 'roomThemes']);

Route::get('super-boom-rules', [SuperBoomRuleController::class, 'index']);
