<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\SalaryTransaction\Http\Controllers\Api\AgencyController;
use Modules\SalaryTransaction\Http\Controllers\Api\AgentSalaryTransactionController;
use Modules\SalaryTransaction\Http\Controllers\Api\SalaryTransactionController;

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
Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan', 'userBan' ,'update.last.seen'])->group (
    function (){
        Route::prefix('salary-transaction')->group(function () {
            Route::post('add-request', [SalaryTransactionController::class, 'add_request_salary']);
            Route::get('get-requests', [SalaryTransactionController::class, 'get_requests']);
            Route::post('action-request', [SalaryTransactionController::class, 'action_request']);
            Route::post('transfer-salary', [SalaryTransactionController::class, 'transfer_salary']);
            Route::get('host-requests', [SalaryTransactionController::class, 'host_requests']);
            Route::post('host-action', [SalaryTransactionController::class, 'host_action']);
        });
        Route::prefix('salary-transaction-agent')->group(function () {
            Route::post('add-request', [AgentSalaryTransactionController::class, 'add_request_salary']);
        });

        Route::get('charge-country', [AgentSalaryTransactionController::class, 'charge_country']);

        Route::prefix('agencies')->group(function () {
            Route::get('/get-info/{agency?}', [AgencyController::class, 'get_info']);
            Route::post('/update-info', [AgencyController::class, 'update_info']);
            Route::post('search-agent', [AgentSalaryTransactionController::class, 'searchAgent']);
            Route::post('v2/search-agent', [AgentSalaryTransactionController::class, 'searchAgentV2']);
            Route::post('shipping-agencies', [AgentSalaryTransactionController::class, 'shipping_agencies']);
            Route::post('charge_co_for_users2', [AgentSalaryTransactionController::class, 'send_money_for_the_host']);
            Route::get('charge-agent-history', [AgentSalaryTransactionController::class, 'chargeCoForUserHistory']);
            Route::get( 'hosts-agency-dollars-history', [AgentSalaryTransactionController::class, 'chargeDollarForUserHistory']);

        });

    }
);
