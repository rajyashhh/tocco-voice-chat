<?php

use Illuminate\Http\Request;
use Modules\AgencyApp\Http\Controllers\Api\V2\AgencyAppController;
use Modules\AgencyApp\Http\Controllers\Api\AgencyAppController as LegacyAgencyAppController;
use Modules\AgencyApp\Http\Controllers\Api\V2\Dashboard\AgencyHostInviteController;
use Modules\AgencyApp\Http\Controllers\Api\V2\Dashboard\AgencyAppController as ApiAgencyAppController;

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

Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan', 'appFeatureEnable:agencies' ,'update.last.seen'])->group(function () {
    Route::post('create-agency', [AgencyAppController::class, 'createAgency']);
    Route::post('action-request-agency', [AgencyAppController::class, 'actionRequestAgency']);
    Route::get('all-agency-request', [AgencyAppController::class, 'allAgencyRequest']);
    Route::get('agency-last-thirty-day', [AgencyAppController::class, 'agency_last_thirty_day']);
    Route::get('agency-total-reports', [AgencyAppController::class, 'agency_total_reports']);
    // These two methods only exist on the base Api\AgencyAppController (not V2).
    Route::post('cancel-request-createAgency', [LegacyAgencyAppController::class, 'cancel_request_createAgency']);
    Route::get('agency-request-info', [LegacyAgencyAppController::class, 'agency_request_info']);
    // Route::get('user-agency-information', [AgencyAppController::class, 'user_agency_information']);
    Route::prefix('agencies')->group(function () {
        Route::post('request-leave-agency', [AgencyAppController::class, 'leave_agency']);
        Route::post('history-data-agency', [AgencyAppController::class, 'historyDataAgency']);
        // Route::post('make-user-as-operator', [AgencyAppController::class, 'make_user_handling_requests']);
        Route::post('kick-of-agency', [AgencyAppController::class, 'kick_of_agency']);
        Route::post('/filter', [AgencyAppController::class, 'agency_filter']);
        Route::post('host-reports', [AgencyAppController::class, 'dailyReport']);//host center
    });

    Route::prefix('v2/agencies')->group(function () {
        Route::post('/filter', [AgencyAppController::class, 'agencyFilterV2']);
        Route::post('/masters', [AgencyAppController::class, 'masters']);
    });

    //dashboard
    Route::get('agency-data', [ApiAgencyAppController::class, 'agency_data']);
    Route::get('host-report/{id}', [ApiAgencyAppController::class, 'host_report']);
    Route::post('host-daily-report', [ApiAgencyAppController::class, 'host_daily_report']);
    Route::post('/host-daily-export-data', [ApiAgencyAppController::class, 'host_daily_export_data']);
    Route::post('/invite-user-to-hostAgency', [AgencyHostInviteController::class, 'invite_user_to_hostAgency']);
    Route::get('/get-invite-agency', [AgencyHostInviteController::class, 'agencyHostInvitation']);
    Route::post('/action-invite-agency', [AgencyHostInviteController::class, 'actionInvitation']);
    Route::post('/host-agency-edit', [ApiAgencyAppController::class, 'host_agency_edit']);
});

