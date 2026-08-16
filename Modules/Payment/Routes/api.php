<?php

use Illuminate\Http\Request;
use Modules\Payment\Http\Controllers\CashFreeController;

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


Route::middleware('auth:sanctum' )->group (
    function (){
        // REMOVED (2026-08-16): PaymentController has no initial() method; the client
        // uses payment-create instead.

        Route::get('payment-create', [\Modules\Payment\Http\Controllers\PaymentController::class, 'create']);
    }
);

Route::get('/payment-callback/{reference_id?}',[\Modules\Payment\Http\Controllers\PaymentController::class,'payment_verify'])->name('payment-verify');
Route::prefix('cashfree')->name('cashfree.')->group(function() {
    Route::post('webhook', [CashFreeController::class, 'webhook'])->middleware('cashfree.verify');
    Route::get('process', [CashFreeController::class, 'store'])->middleware('auth:sanctum')->name('store');
    Route::get('status', [CashFreeController::class,'orderStatus'])->name('status');

//    Route::any('payments/success', [CashFreeController::class, 'success'])->name('success');
});
