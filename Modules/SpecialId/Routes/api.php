<?php

use Illuminate\Http\Request;
use Modules\SpecialId\Http\Controllers\Api\SpecialIdController;

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
    Route::post('buy-special-id',[SpecialIdController::class,'buySpecialId']);
    Route::post('use-special-id',[SpecialIdController::class,'usePackItem']);
    Route::post('upload-special-id',[SpecialIdController::class,'upload_special_id']);
    Route::get('special-frame',[SpecialIdController::class,'specialIdFrame']);
    Route::get('special-users',[SpecialIdController::class,'specialUsers']);
});
