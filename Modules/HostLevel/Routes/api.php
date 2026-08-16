<?php

use Illuminate\Http\Request;
use Modules\HostLevel\Http\Controllers\api\HostLevelController;

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

Route::middleware(['auth:sanctum', 'update.last.seen'])->group(function () {

   
    Route::middleware(['host.level'])->prefix('host-level')->group(function () {
        Route::get('/', [HostLevelController::class, 'hostLevel']);
        Route::post('/pick', [HostLevelController::class, 'pick']);
    });
});
