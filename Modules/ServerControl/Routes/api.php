<?php

use Illuminate\Http\Request;
use Modules\ServerControl\Http\Controllers\Api\AppEarnedController;
use Modules\ServerControl\Http\Controllers\Api\ConfigControlController;

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

Route::middleware('auth:api')->get('/servercontrol', function (Request $request) {
    return $request->user();
});


Route::prefix('')->middleware(['auth:sanctum' ,'update.last.seen'])->group(function(){
    Route::resource('configs', ConfigControlController::class)->except('show', 'edit')->middleware("configM");
    Route::get('app-earned', [AppEarnedController::class, 'index'])->middleware("configM");
});