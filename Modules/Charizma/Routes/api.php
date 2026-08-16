<?php

namespace Modules\Charizma\Http\Services;


use Illuminate\Http\Request;
use Modules\Charizma\Http\Controllers\CharizmaController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum','appFeatureEnable:charizma' ,'update.last.seen'])->group(function () {
    Route::prefix('charisma')->group(function () {
        // Charisma is fully client-side now (owner decision): only the visibility
        // toggle remains. The reset endpoint is gone — there is no backend store.
        Route::post('/change-status', [CharizmaController::class,'changeStatus']);
    });
});
