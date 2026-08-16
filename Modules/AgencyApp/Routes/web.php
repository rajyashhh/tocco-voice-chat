<?php

use Modules\AgencyApp\Http\Controllers\web\RequestAgencyController;
use Modules\AgencyApp\Http\Controllers\web\RecommendationAgencyController;
use Modules\AgencyApp\Http\Controllers\web\RequestAgencyFilterationController;
use Modules\AgencyApp\Http\Controllers\web\HostReportController;

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

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => 'web',
    'middleware' => [
        'web',
        'admin',
        'adminIp',
        // 'adminGeneralBan',
        'multiLanguage',
    ],
    'as' => config('admin.route.prefix') . '.',
], function () {
    Route::resource('request-agencies', RequestAgencyController::class);
    Route::resource('request-agencies-filteration', RequestAgencyFilterationController::class);
    Route::resource('recommendation-agencies', RecommendationAgencyController::class);
});

    Route::get('host-reports', [HostReportController::class, 'dailyReport']);
