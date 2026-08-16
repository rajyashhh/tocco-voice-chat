<?php

/*
|--------------------------------------------------------------------------
| api Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Achievement\Http\Services\AchievementLevelsService;

Route::middleware(['auth:sanctum','appFeatureEnable:achievement' ,'update.last.seen'])->group(function () {
    Route::get('/test/achievement', function () {
        (new AchievementLevelsService())->setUserAchievementLevel(true);
        return response()->json('Success');
    });
    Route::prefix('achievement')->group(function () {
        Route::get('/{id}', 'AchievementController@get_all');
        Route::get('/', 'AchievementController@get_all');
        Route::get('/user/{id}', 'AchievementLevelController@show');
    });

    Route::post('/user-achievement-select', 'AchievementController@achivement_select');
    Route::get('/achievement-all', 'AchievementController@get_all');
    Route::get('/achievements-details/{id?}', 'AchievementController@get_details');
    Route::get('/achievements-picked/{id?}', 'AchievementController@get_all_select');

});
