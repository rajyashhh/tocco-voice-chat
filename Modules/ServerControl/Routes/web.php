<?php

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

use Modules\ServerControl\Http\Controllers\web\ConfigAppController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'namespace'  => 'web',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
            'appFeatureEnable:whatsapp',
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('config-apps', ConfigAppController::class);
    }
);
