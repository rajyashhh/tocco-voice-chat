<?php

use Modules\RoomCup\Http\Controllers\web\RoomCupTargetController;
use Modules\RoomCup\Http\Controllers\web\RoomCupSettingsController;
use Modules\RoomCup\Http\Controllers\web\RoomCupReportsController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
             'check.allowed.app',
            // 'room.cup'
        ],
        'as'         => config('admin.route.prefix') . 'routes',
    ],
    function () {
        Route::resource('room-cup-target', RoomCupTargetController::class);
        Route::resource('room-cup-settings', RoomCupSettingsController::class);
        Route::post('room-cup-settings/save', [RoomCupSettingsController::class, 'save'])->name('room-cup-settings.save');

        Route::resource('room-cup-reports', RoomCupReportsController::class);




    }
);

Route::middleware(['admin'])->get('cup-targets-view', [RoomCupTargetController::class,'cupTargetHtml']);

Route::middleware(['admin'])->get('/roomcup/calculate-rewards', function () {

    Artisan::call('roomcup:calculate-rewards');

    $output = Artisan::output();

    return response()->json([
        'status'  => 'success',
        'message' => 'RoomCup rewards calculated',
        'output'  => $output,
    ]);
});
