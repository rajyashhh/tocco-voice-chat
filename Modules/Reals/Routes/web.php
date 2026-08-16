<?php

use Modules\Reals\Http\Controllers\web\ReelController;
use Modules\Reals\Http\Controllers\web\ReportRealsController;
use Modules\Reals\Http\Controllers\web\ReelSettingsController;
use Modules\Public\Http\Controllers\web\UpgradeLevelController;
use Modules\Reals\Http\Controllers\web\AdminReelController;
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

Route::prefix('reals')->middleware("appFeatureEnable:reel")->group(function() {
    Route::get('/', 'RealsController@index');
    Route::get('delete-reel/{real_id}/{id}', 'RealsController@destroy_dash')->name('delete-reel')->middleware(['appFeatureEnable:reel']);
});

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
        ],
        'as'         => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('report-reals', ReportRealsController::class);
        Route::resource('reels', ReelController::class);
        Route::resource('reel-settings', ReelSettingsController::class);
        Route::post('reel-config', [UpgradeLevelController::class, 'reelConfig'])->name('reel-config');


        Route::prefix('view')->group(function () {
            Route::get('/reels', [AdminReelController::class, 'index'])->name('admin.reels.index');
            Route::get('/reels/load-more', [AdminReelController::class, 'loadMore'])->name('admin.reels.loadMore');
            Route::post('/reels/load-more', [AdminReelController::class, 'loadMore'])->name('admin.reels.loadMore.post');
            Route::post('/reels/batch-counts', [AdminReelController::class, 'batchCounts'])->name('admin.reels.batchCounts');
            Route::get('/reels/{id}', [AdminReelController::class, 'show'])->name('admin.reels.show');
            Route::post('/reels/{id}/update', [AdminReelController::class, 'update'])->name('admin.reels.update');
            Route::delete('/reels/{id}', [AdminReelController::class, 'destroy'])->name('admin.reels.destroy');
            Route::get('/reels/{id}/likes', [AdminReelController::class, 'getLikes'])->name('admin.reels.likes');
            Route::get('/reels/{id}/comments', [AdminReelController::class, 'getComments'])->name('admin.reels.comments');
            Route::get('/reels/{id}/gifts', [AdminReelController::class, 'getGifts'])->name('admin.reels.gifts');
        });

    }
);
