<?php

use App\Http\Controllers\Api\V1\UtdStreamController;
use Illuminate\Support\Facades\Route;

// ─── UTD-STREAM ─────────────────────────────────
Route::prefix('stream')->group(function () {
    // ─── Authentication ─────────────────────────
    Route::post('token', [UtdStreamController::class, 'token']);
    Route::get('credential', [UtdStreamController::class, 'credential']);

    // ─── Rooms Management ───────────────────────
    Route::get('rooms', [UtdStreamController::class, 'getRooms']);
    Route::get('rooms/{roomName}', [UtdStreamController::class, 'roomInfo']);
    Route::delete('rooms/{roomName}', [UtdStreamController::class, 'deleteRoom']);
    Route::patch('rooms/{roomName}/metadata', [UtdStreamController::class, 'patchRoomMetadata']);
    Route::post('rooms/{roomName}/send-data', [UtdStreamController::class, 'sendData']);

    // ─── Participant Management ─────────────────
    Route::get('rooms/{roomName}/participants/{identity}', [UtdStreamController::class, 'participantInfo']);
    Route::delete('rooms/{roomName}/participants/{identity}', [UtdStreamController::class, 'kick']);
    Route::put('rooms/{roomName}/participants/{identity}/permissions', [UtdStreamController::class, 'updateParticipantPermissions']);
    Route::put('rooms/{roomName}/participants/{identity}/metadata', [UtdStreamController::class, 'setParticipantMetadata']);
    Route::put('rooms/{roomName}/participants/{identity}/mute', [UtdStreamController::class, 'mute']);
    Route::put('rooms/{roomName}/participants/{identity}/forbid-stream', [UtdStreamController::class, 'forbidPublishing']);
    Route::put('rooms/{roomName}/participants/{identity}/resume-stream', [UtdStreamController::class, 'resumePublishing']);

    // ─── Ban Management ─────────────────────────
    Route::post('rooms/ban', [UtdStreamController::class, 'createBan']);
    Route::delete('rooms/ban', [UtdStreamController::class, 'removeBan']);
    Route::get('rooms/bans', [UtdStreamController::class, 'getBans']);

    // ─── Calls API (1-on-1) ─────────────────────
    Route::get('calls', [UtdStreamController::class, 'getCalls']);
    Route::post('calls', [UtdStreamController::class, 'makeCall']);
    Route::get('calls/{callId}', [UtdStreamController::class, 'callInfo']);
    Route::post('calls/{callId}/ringing', [UtdStreamController::class, 'ringing']);
    Route::post('calls/{callId}/accept', [UtdStreamController::class, 'accept']);
    Route::post('calls/{callId}/reject', [UtdStreamController::class, 'reject']);
    Route::post('calls/{callId}/busy', [UtdStreamController::class, 'busy']);
    Route::post('calls/{callId}/end', [UtdStreamController::class, 'end']);

    // ─── Project Info ───────────────────────────
    Route::get('project', [UtdStreamController::class, 'projectInfo']);

    // ─── Webhooks - Events (16 events) ─────────
    Route::prefix('webhooks')->group(function () {
        // Rooms & Streaming (6 events)
        Route::post('room_started', [UtdStreamController::class, 'onRoomStarted']);
        Route::post('room_finished', [UtdStreamController::class, 'onRoomFinished']);
        Route::post('participant_joined', [UtdStreamController::class, 'onParticipantJoined']);
        Route::post('participant_left', [UtdStreamController::class, 'onParticipantLeft']);
        Route::post('track_published', [UtdStreamController::class, 'onTrackPublished']);
        Route::post('track_unpublished', [UtdStreamController::class, 'onTrackUnpublished']);

        // Calls (7 events)
        Route::post('call_initiated', [UtdStreamController::class, 'onCallInitiated']);
        Route::post('call_ringing', [UtdStreamController::class, 'onCallRinging']);
        Route::post('call_accepted', [UtdStreamController::class, 'onCallAccepted']);
        Route::post('call_rejected', [UtdStreamController::class, 'onCallRejected']);
        Route::post('call_busy', [UtdStreamController::class, 'onCallBusy']);
        Route::post('call_ended', [UtdStreamController::class, 'onCallEnded']);
        Route::post('call_missed', [UtdStreamController::class, 'onCallMissed']);

        // Presence (2 events)
        Route::post('user_online', [UtdStreamController::class, 'onUserOnline']);
        Route::post('user_offline', [UtdStreamController::class, 'onUserOffline']);

        // Messaging (1 event)
        Route::post('message_sent', [UtdStreamController::class, 'onMessageSent']);
    });
});
