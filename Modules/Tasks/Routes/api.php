<?php

use Illuminate\Http\Request;
use Modules\Tasks\Http\Controllers\DayTasksController;
use Modules\Tasks\Http\Controllers\TaskCompleteController;
use Modules\Tasks\Http\Controllers\TaskProgressController;

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

//include base_path('Modules/Tasks/Routes/api.php');

Route::middleware('auth:api')->get('/tasks', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum' ,'update.last.seen'])->group(function () {
    Route::get('days', [TaskProgressController::class, 'getDays']);
    Route::post('tasks/{taskId}/collect', [TaskCompleteController::class, 'collectTaskPoints']);
    Route::post('day/tasks/{taskId}', [DayTasksController::class, 'getDayTasks']);
    
});

//is_collect==>user_tasks, get_rewards==>days
//Route::get('user/{userId}/progress', [TaskProgressController::class, 'getUserProgress']);