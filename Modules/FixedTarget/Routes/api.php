<?php

use Illuminate\Http\Request;
use Modules\FixedTarget\Services\FixedTargetService;
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

// Route::post("test-calculateTarget",function(){
//     $user =\App\Models\User::find(9);
//     $fixedTargetService = new FixedTargetService($user);
//     $fixedTargetService->calculateTarget();
// });
Route::middleware('auth:api')->get('/fixedtarget', function (Request $request) {
    return $request->user();
});
