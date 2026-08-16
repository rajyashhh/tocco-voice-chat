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


// Route::prefix('userswallet')->group(function() {
//     Route::get('/', 'UsersWalletController@index');
// });

use Modules\UsersWallet\Http\Controllers\Web\UserWithdrawalController;
use Modules\UsersWallet\Http\Controllers\Web\WalletFieldController;
use Modules\UsersWallet\Http\Controllers\Web\WalletTemplateController;

Route::group(
    [
        'prefix'     => config('admin.route.prefix'),
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {
        Route::resource('wallet-withdrawal', UserWithdrawalController::class);

        
        Route::post('/wallet/withdrawals/{id}/approve', [UserWithdrawalController::class,'approve'])->name('withdrawals.approve');
        Route::post('/wallet/withdrawals/{id}/reject', [UserWithdrawalController::class,'reject'])->name('withdrawals.reject');


                Route::resource('wallet-templates', WalletTemplateController::class);

        Route::prefix('wallet-fields/{wallet_template_id}')->group(function () {
            Route::get('/', [WalletFieldController::class, 'index'])->name('wallet-fields.index');
            Route::get('/create', [WalletFieldController::class, 'create'])->name('wallet-fields.create');
            Route::post('/', [WalletFieldController::class, 'store'])->name('wallet-fields.store');
            Route::get('/{id}', [WalletFieldController::class, 'show'])->where('id', '[0-9]+')->name('wallet-fields.show');
            Route::get('/{id}/edit', [WalletFieldController::class, 'edit'])->where('id', '[0-9]+')->name('wallet-fields.edit');
            Route::put('/{id}', [WalletFieldController::class, 'update'])->where('id', '[0-9]+')->name('wallet-fields.update');
            Route::delete('/{id}', [WalletFieldController::class, 'destroy'])->where('id', '[0-9]+')->name('wallet-fields.destroy');
        });
    
    });




