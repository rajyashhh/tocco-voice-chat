<?php

use Illuminate\Http\Request;
use Modules\SwitchAccount\Http\Controllers\SwitchAccountController;

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
Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan'])->group(function () {
    Route::post('add-account',[SwitchAccountController::class,'add_account']);
    Route::post('switch-account',[SwitchAccountController::class,'switch_account']);
    Route::get('my-accounts',[SwitchAccountController::class,'myAccounts']);
});
