<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Form\Http\Controllers\CustomWidgetController;
use Modules\Form\Http\Controllers\FormTemplateController;
use Modules\Form\Http\Controllers\Api\DataSourceController;
use Modules\Form\Http\Controllers\FormSubmissionController;








Route::middleware('auth:sanctum')->group(function () {
    Route::get('form-list', [DataSourceController::class, 'formList']);
});









Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        // Route::apiResource('form-templates', FormTemplateController::class);
        // NOTE: form-sections / form-fields apiResource routes removed — the
        // referenced controllers (FormSectionController / FormFieldController)
        // never existed in the repository.
        // REMOVED (2026-08-16): FormSubmissionController has submit(), not store();
        // submission is handled via the web route forms/{template}/submit.
    });
});

// Custom Widget API Routes
Route::prefix('widgets')->name('widgets.')->group(function () {
    Route::get('/', [CustomWidgetController::class, 'index'])->name('index');
    Route::get('/{widget}/data', [CustomWidgetController::class, 'getData'])->name('data');
    Route::get('/bd-users', [CustomWidgetController::class, 'getBDUsers'])->name('bd-users');
    Route::get('/agencies', [CustomWidgetController::class, 'getAgencies'])->name('agencies');
    Route::get('/users', [CustomWidgetController::class, 'getUsers'])->name('users');
});

// Data Source API Routes (for predefined select options)
Route::prefix('data-sources')->name('data-sources.')->group(function () {
    Route::get('/{source}', [DataSourceController::class, 'getData'])->name('get');
});