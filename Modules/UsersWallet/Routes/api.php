<?php

use Modules\UsersWallet\Http\Controllers\Api\ExchangeController;
use Modules\UsersWallet\Http\Controllers\Api\UsersWalletController;
use Modules\UsersWallet\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\V1\ChargeController;


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


Route::group(['prefix' => 'wallets', 'middleware' => ['auth:sanctum', 'checkLatestToken', 'generalBan', 'userBan', 'localization']], function (){
    Route::get('/withdraw-methods', [UsersWalletController::class, 'withdrawMethods']);
    Route::post('/withdraw', [UsersWalletController::class, 'requestWithdrawal'])->middleware('auth:sanctum');
   Route::post('/transfer', [UsersWalletController::class, 'transferToUser']);
//    Route::post('/transfer', [ChargeController::class, 'chargeTo']);
    Route::get('/profits', [WalletController::class, 'getProfitsByType']);
    Route::get('/latest-operations', [WalletController::class, 'getLatestOperations']);

  
    Route::get('/getTemplate', [WalletController::class, 'getTemplate']);
    Route::get('/transactions', [WalletController::class, 'getWalletTransactions']);
    Route::get('diamonds-statistic', [WalletController::class, 'diamondsStatistic']);
    Route::get('history', [WalletController::class, 'history']);

    Route::prefix('exchange')->group(function () {
        Route::get('/list', [ExchangeController::class, 'exchangeList']);
        Route::get('/v2/list', [ExchangeController::class, 'exchangeSettingNumber']);
        Route::post('/make', [ExchangeController::class, 'exchangeSave']);
        Route::post('/v2/make', [ExchangeController::class, 'exchangeCoin']);
        Route::get('/logs', [ExchangeController::class, 'exchangeLogs']);
    });
});


 