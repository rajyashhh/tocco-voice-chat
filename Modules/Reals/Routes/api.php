<?php

use Illuminate\Http\Request;
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

Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan','userBan' ,'update.last.seen'])->group (
    function (){

        Route::prefix('/')->middleware("appFeatureEnable:reel")->group(function (){
            Route::get('reals/user/{user_id?}', 'RealsController@getUserReals')->middleware('ban.user.actions:reals/user');;
            Route::get('reals/my-reals', 'RealsController@getMyReals');
            Route::get('reals/user-followers', 'RealsController@getUserFollowersReals');
            Route::post('reals/{id}/view', 'RealsController@recordView');
            Route::apiResource('/reals', 'RealsController')->middleware('ban.user.actions:reals');;
            Route::post('reals-update/{id}', 'RealsController@update');
            Route::apiResource('reals/{real_id}/comment', 'RealsUserCommentController', [
                'names' => [
                    'index' => 'reals.comment.index',
                    'store' => 'reals.comment.store',
                    'show' => 'reals.comment.show',
                    'update' => 'reals.comment.update',
                    'destroy' => 'reals.comment.destroy',
                ],
                'except' => ['show'],
            ]);
            Route::apiResource('reals/{real_id}/like', 'RealsUserLikesController', [
                'names' => [
                    'index' => 'reals.like.index',
                    'store' => 'reals.like.store',
                    'show' => 'reals.like.show',
                    'update' => 'reals.like.update',
                    'destroy' => 'reals.like.destroy',
                ],
                'except' => ['show'],
            ]);
            Route::apiResource('/report', 'ReportController');
        });

    }
);
