<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\LiveTime;
use App\Tik\Services\RoomOccupancyReconciler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtdStreamWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from UTD-Stream
     */
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $data = $request->all();

        Log::info('UTD-Stream webhook received', [
            'event' => $event,
            'data' => $data,
        ]);

        try {
            match ($event) {
                // Room Events
                'room_started' => $this->handleRoomStarted($data),
                'room_finished' => $this->handleRoomFinished($data),
                'participant_joined' => $this->handleParticipantJoined($data),
                'participant_left' => $this->handleParticipantLeft($data),
                'track_published' => $this->handleTrackPublished($data),
                'track_unpublished' => $this->handleTrackUnpublished($data),

                // Call Events
                'call_initiated' => $this->handleCallInitiated($data),
                'call_ringing' => $this->handleCallRinging($data),
                'call_accepted' => $this->handleCallAccepted($data),
                'call_rejected' => $this->handleCallRejected($data),
                'call_busy' => $this->handleCallBusy($data),
                'call_ended' => $this->handleCallEnded($data),
                'call_missed' => $this->handleCallMissed($data),

                default => Log::warning('Unknown UTD-Stream webhook event', ['event' => $event]),
            };

            return response()->json(['status' => 'ok'], 200);
        } catch (\Exception $e) {
            Log::error('UTD-Stream webhook processing error', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Normalize the engine's track.type to the documented string form.
     *
     * The engine forwards the RAW media-server event under the default (v1)
     * callback schema, where TrackInfo.type is a NUMERIC protobuf enum
     * (0=AUDIO, 1=VIDEO, 2=DATA) — NOT the string name. Under v2 it already
     * arrives as 'AUDIO'/'VIDEO'. Accept both so a schema flip never silently
     * breaks these handlers (this mirrors trackTypeName() in the engine's
     * routes/webhook.js and neutralizeTrack() in utils/callbackNeutralize.js).
     */
    private const TRACK_TYPE_NAMES = [0 => 'AUDIO', 1 => 'VIDEO', 2 => 'DATA'];

    private function trackType(array $data): ?string
    {
        $type = $data['track']['type'] ?? null;

        if (is_int($type) || (is_string($type) && ctype_digit($type))) {
            return self::TRACK_TYPE_NAMES[(int) $type] ?? null;
        }

        return is_string($type) ? strtoupper($type) : null;
    }

    // ─── Room Event Handlers ─────────────────────────────────

    private function handleRoomStarted($data)
    {
        // Intentionally a no-op for listing: room_started fires when the LiveKit
        // room is created (host connecting / preparing), which is exactly the
        // "too early" moment we must NOT list on. A room becomes visible only on
        // the first host VIDEO track_published (handleTrackPublished).
    }

    private function handleRoomFinished($data)
    {
        // Real-time mirror of the full-sync deactivation: when the LiveKit room
        // ends, clear local occupancy and flip is_live off. Same reconciler used
        // by the rooms:sync-occupancy command — no duplicated logic.
        $roomName = $data['room']['name'] ?? null;
        $room = app(RoomOccupancyReconciler::class)->resolveRoom($roomName);

        if (! $room) {
            return;
        }

        app(RoomOccupancyReconciler::class)->deactivateRoom($room);
    }

    private function handleParticipantJoined($data)
    {
        // Real-time mirror of the room_login path: add the participant (a
        // users.id, per UtdStreamController::token identity) to room_visitors.
        $roomName = $data['room']['name'] ?? null;
        $userId = (int) ($data['participant']['identity'] ?? 0);

        if ($userId <= 0) {
            return;
        }

        $room = app(RoomOccupancyReconciler::class)->resolveRoom($roomName);
        if (! $room) {
            return;
        }

        app(RoomOccupancyReconciler::class)->addParticipant($room, $userId);
    }

    private function handleParticipantLeft($data)
    {
        // Real-time mirror of the room_logout path: remove the participant from
        // room_visitors and flip is_live off if the room is now empty.
        $roomName = $data['room']['name'] ?? null;
        $userId = (int) ($data['participant']['identity'] ?? 0);

        if ($userId <= 0) {
            return;
        }

        // Leaving the room always ends mic time — covers crash-exits where
        // track_unpublished may never arrive. No-op without an open timer.
        $this->closeLiveTimer($userId);

        $room = app(RoomOccupancyReconciler::class)->resolveRoom($roomName);
        if (! $room) {
            return;
        }

        // Owner left (crash / clean exit): the broadcast is over regardless of
        // lingering audience — delist it. Non-owner leaves never affect it.
        if ((int) $room->uid === $userId) {
            app(RoomOccupancyReconciler::class)->setBroadcasting($room, false);
        }

        app(RoomOccupancyReconciler::class)->removeParticipant($room, $userId);
    }

    /**
     * live_times writer for the UTD Stream room flow.
     *
     * The legacy writers (POST rooms/up-microphone | leave-microphone |
     * liveTime) died with the engine-room rollout on 2026-06-04 — the new
     * client takes mic seats through the engine, so the host live-hours feed
     * (salaries) went silent. In the engine, publishing an audio track IS
     * being on a mic seat (audience can't publish), so these webhooks are the
     * authoritative successor: AUDIO track_published opens a timer (mirrors
     * MicService::upMic2), AUDIO track_unpublished / participant_left closes
     * it (same math as UserHandling::calcTime).
     */
    private function handleTrackPublished($data)
    {
        $trackType = $this->trackType($data);
        $userId = (int) ($data['participant']['identity'] ?? 0);

        // A VIDEO track from the room OWNER is the authoritative "broadcast has
        // actually started" signal — the exact moment the room may enter the
        // lives/trending list. Set it here (not on room enter / room_started).
        if ($trackType === 'VIDEO' && $userId > 0) {
            $roomName = $data['room']['name'] ?? null;
            $room = app(RoomOccupancyReconciler::class)->resolveRoom($roomName);
            if ($room && (int) $room->uid === $userId) {
                app(RoomOccupancyReconciler::class)->setBroadcasting($room, true);
            }
        }

        if ($trackType !== 'AUDIO') {
            return;
        }

        if ($userId <= 0) {
            return;
        }

        // Open timers can only be closed same-day (calcTime scopes to today),
        // so drop stale open rows from previous days first — mirrors the
        // legacy cleanup in RoomController::up_microphone.
        LiveTime::query()
            ->where('uid', $userId)
            ->whereNull('end_time')
            ->whereDate('created_at', '!=', today())
            ->delete();

        $hasOpenTimer = LiveTime::query()
            ->where('uid', $userId)
            ->whereNull('end_time')
            ->whereDate('created_at', today())
            ->exists();

        if (! $hasOpenTimer) {
            LiveTime::query()->create([
                'uid' => $userId,
                'start_time' => time(),
            ]);
        }
    }

    private function handleTrackUnpublished($data)
    {
        $trackType = $this->trackType($data);
        $userId = (int) ($data['participant']['identity'] ?? 0);

        // Owner stopped their camera: the broadcast is no longer live. Delist it
        // immediately (participant_left / room_finished are the other clear
        // paths; the occupancy sync is the backstop for a missed webhook).
        if ($trackType === 'VIDEO' && $userId > 0) {
            $roomName = $data['room']['name'] ?? null;
            $room = app(RoomOccupancyReconciler::class)->resolveRoom($roomName);
            if ($room && (int) $room->uid === $userId) {
                app(RoomOccupancyReconciler::class)->setBroadcasting($room, false);
            }
        }

        if ($trackType !== 'AUDIO') {
            return;
        }

        if ($userId > 0) {
            $this->closeLiveTimer($userId);
        }
    }

    private function closeLiveTimer(int $userId): void
    {
        // UserHandling::calcTime closes today's open timer, computes hours and
        // updates today_days — identical to the legacy leave-microphone path.
        // Guard the lookup: identities always come from issued tokens, but a
        // since-deleted user must not 500 the webhook (the relay would retry).
        if (! \App\Models\User::query()->whereKey($userId)->exists()) {
            return;
        }

        UserHandling::calcTime($userId);
    }

    // ─── Call Event Handlers ─────────────────────────────────

    private function handleCallInitiated($data)
    {
        // TODO: Send push notification to callee
        // Example:
        // $calleeId = $data['call']['callee_identity'] ?? null;
        // $callerId = $data['call']['caller_identity'] ?? null;
        // $callId = $data['call']['call_id'] ?? null;
        //
        // if ($calleeId) {
        //     sendPushNotification($calleeId, [
        //         'type' => 'incoming_call',
        //         'call_id' => $callId,
        //         'caller' => $callerId,
        //     ]);
        // }
    }

    private function handleCallRinging($data)
    {
        // TODO: Update call status, notify caller that callee device is ringing
    }

    private function handleCallAccepted($data)
    {
        // TODO: Update call status, notify both parties
    }

    private function handleCallRejected($data)
    {
        // TODO: Update call status, notify caller
    }

    private function handleCallBusy($data)
    {
        // TODO: Update call status, notify caller that callee is busy
    }

    private function handleCallEnded($data)
    {
        // TODO: Update call logs, calculate duration, notify parties
        // $duration = $data['call']['duration_seconds'] ?? 0;
        // $endReason = $data['call']['end_reason'] ?? 'unknown';
    }

    private function handleCallMissed($data)
    {
        // TODO: Create missed call notification
        // Example:
        // $calleeId = $data['call']['callee_identity'] ?? null;
        // $callerId = $data['call']['caller_identity'] ?? null;
        //
        // if ($calleeId) {
        //     createNotification($calleeId, [
        //         'type' => 'missed_call',
        //         'from' => $callerId,
        //     ]);
        // }
    }
}
