<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\WhatsappAuth\Http\Controllers\WhatsappController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('whatsappauth', fn (Request $request) => $request->user())->name('whatsappauth');
});

// WhatsApp OTP AUTH routes removed — phone verification migrated to Firebase Phone Auth (firebase_id_token).
// Disabled: verification_code, register-whatsapp, forget-password-whatsapp, whatsapp-webhook,
//           send-code-whatsapp, change-phone-whatsapp, reset-password-whatsapp.
