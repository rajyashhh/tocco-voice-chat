<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Traits\HelperTraits\UtdStreamTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UtdStreamController extends Controller
{
    use UtdStreamTrait;

    // ─── Token ───────────────────────────────────────────────

    public function token(Request $request)
    {
        if (is_string($request->input('os'))) {
            $request->merge(['os' => strtolower($request->input('os'))]);
        }

        $request->validate([
            'room_name' => 'required|string',
            // 'admin' is a real engine role (moderators join with it); the seat
            // params are host-only layout hints the engine expects at mint time.
            'role'       => 'nullable|string|in:host,admin,guest,audience,visitor',
            'service'    => 'required|string|in:rooms,streaming',
            'room_owner_id' => 'nullable|string',
            'seat_count' => 'nullable|integer|min:1|max:64',
            'seat_mode'  => 'nullable|string|in:free,request',
            'host_seat'  => 'nullable|integer|min:0',
            'mode_id'    => 'nullable|string|max:16',
            // Engine gates the beauty-filter (videoEffects) entitlement on the
            // caller OS at mint time — fail-closed when absent.
            'os'         => 'nullable|string|in:android,ios',
            'device_model' => 'nullable|string|max:64',
            'os_version'   => 'nullable|string|max:32',
            'app_version'  => 'nullable|string|max:32',
        ]);

        $user = $request->user();
        $identity = (string) $user->id;
        $name = $user->name ?? $user->uuid ?? $identity;

        // Voice-engine credentials are per-install and empty until the operator
        // fills them in. Say so explicitly here rather than letting the token
        // request fail with a generic 500 the client shows as "something went
        // wrong" — this is the exact point a fresh white-label breaks on room open.
        $streamData = self::streamData();
        if (empty($streamData['app_id']) || empty($streamData['server_secret'])) {
            return Common::apiResponse(
                false,
                'Voice engine is not configured yet. Set the UTD Stream credentials (App ID + Server Secret) in the admin settings before opening rooms.',
                null,
                503
            );
        }

        $extra = $request->only(['room_owner_id', 'seat_count', 'seat_mode', 'host_seat', 'mode_id', 'os', 'device_model', 'os_version', 'app_version']);
        // The app's contract with US stays service rooms/streaming, but the
        // production engine (new generation) mints by explicit `type` and
        // deliberately rejects the legacy `service` field. Translate here —
        // single seam — so the shipped app needs no change. `type` also
        // replaces the old `kind: live` hint (legacy service/kind derivation
        // defaulted kind to audio and broke PK battles with 422).
        $extra['type'] = $request->service === 'streaming' ? 'live_stream' : 'audio_room';

        $result = self::generateStreamToken(
            $identity,
            $request->room_name,
            $name,
            $request->role,
            null,
            $extra
        );

        if (!$result) {
            return Common::apiResponse(false, 'Failed to generate token', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Rooms ───────────────────────────────────────────────

    public function getRooms(Request $request)
    {
        $result = self::listRooms();

        if (!$result) {
            return Common::apiResponse(false, 'Failed to get rooms', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function roomInfo(Request $request, $roomName)
    {
        $result = self::getRoomInfo($roomName);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to get room info', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function deleteRoom(Request $request, $roomName)
    {
        $result = self::closeRoom($roomName);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to close room', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function patchRoomMetadata(Request $request, $roomName)
    {
        $request->validate([
            'metadata' => 'required',
        ]);

        $result = self::updateRoomMetadata($roomName, $request->metadata);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to update room metadata', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function sendData(Request $request, $roomName)
    {
        $request->validate([
            'data' => 'required',
        ]);

        $destinations = $request->destination_identities;
        $result = self::streamSendData($roomName, $request->data, $destinations);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to send data', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function kick(Request $request, $roomName, $identity)
    {
        $result = self::kickUser($roomName, $identity);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to kick user', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function mute(Request $request, $roomName, $identity)
    {
        $result = self::muteUser(
            $roomName,
            $identity,
            $request->boolean('audio', true),
            $request->boolean('video', false)
        );

        if (!$result) {
            return Common::apiResponse(false, 'Failed to mute user', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Participant Management ──────────────────────────────

    public function participantInfo(Request $request, $roomName, $identity)
    {
        $result = self::getParticipant($roomName, $identity);

        if (!$result) {
            return Common::apiResponse(false, 'Participant not found', null, 404);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function updateParticipantPermissions(Request $request, $roomName, $identity)
    {
        $request->validate([
            'can_publish' => 'nullable|boolean',
            'can_subscribe' => 'nullable|boolean',
            'can_publish_data' => 'nullable|boolean',
        ]);

        $permissions = $request->only(['can_publish', 'can_subscribe', 'can_publish_data', 'can_publish_sources', 'hidden']);

        $result = self::updatePermissions($roomName, $identity, $permissions);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to update permissions', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function setParticipantMetadata(Request $request, $roomName, $identity)
    {
        $request->validate([
            'metadata' => 'required',
        ]);

        $result = self::updateParticipantMetadata($roomName, $identity, $request->metadata);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to update metadata', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function forbidPublishing(Request $request, $roomName, $identity)
    {
        $result = self::forbidStream($roomName, $identity);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to forbid stream', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function resumePublishing(Request $request, $roomName, $identity)
    {
        $result = self::resumeStream($roomName, $identity);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to resume stream', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Ban Management ──────────────────────────────────────

    public function createBan(Request $request)
    {
        $request->validate([
            'identity' => 'required|string',
            'room_name' => 'nullable|string',
            'reason' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
        ]);

        $result = self::banUser(
            $request->identity,
            $request->room_name,
            $request->reason,
            $request->duration
        );

        if (!$result) {
            return Common::apiResponse(false, 'Failed to ban user', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function removeBan(Request $request)
    {
        $request->validate([
            'identity' => 'required|string',
            'room_name' => 'nullable|string',
        ]);

        $result = self::unbanUser($request->identity, $request->room_name);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to unban user', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function getBans(Request $request)
    {
        $result = self::listBans();

        if (!$result) {
            return Common::apiResponse(false, 'Failed to get bans', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Calls ───────────────────────────────────────────────

    public function getCalls(Request $request)
    {
        $filters = $request->only(['status', 'type', 'identity', 'start_date', 'end_date', 'page', 'per_page']);

        $result = self::listCalls($filters);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to get calls', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Calls ───────────────────────────────────────────────

    public function makeCall(Request $request)
    {
        $request->validate([
            'callee_id' => 'required',
            'type'      => 'nullable|string|in:voice,video',
        ]);

        $user = $request->user();
        $callerIdentity = (string) $user->id;
        $calleeIdentity = (string) $request->callee_id;

        $result = self::initiateCall(
            $callerIdentity,
            $calleeIdentity,
            $request->type ?? 'voice',
            $request->metadata
        );

        if (!$result) {
            return Common::apiResponse(false, 'Failed to initiate call', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function ringing(Request $request, $callId)
    {
        $user = $request->user();
        $result = self::callRinging($callId, (string) $user->id);

        if (!$result) {
            return Common::apiResponse(false, 'Failed', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function accept(Request $request, $callId)
    {
        $user = $request->user();
        $result = self::acceptCall($callId, (string) $user->id);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to accept call', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function reject(Request $request, $callId)
    {
        $user = $request->user();
        $result = self::rejectCall($callId, (string) $user->id);

        if (!$result) {
            return Common::apiResponse(false, 'Failed', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function busy(Request $request, $callId)
    {
        $user = $request->user();
        $result = self::callBusy($callId, (string) $user->id);

        if (!$result) {
            return Common::apiResponse(false, 'Failed', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function end(Request $request, $callId)
    {
        $user = $request->user();
        $result = self::endCall($callId, (string) $user->id);

        if (!$result) {
            return Common::apiResponse(false, 'Failed to end call', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    public function callInfo(Request $request, $callId)
    {
        $result = self::getCall($callId);

        if (!$result) {
            return Common::apiResponse(false, 'Call not found', null, 404);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Project Info ────────────────────────────────────────

    public function projectInfo(Request $request)
    {
        $result = self::getProjectInfo();

        if (!$result) {
            return Common::apiResponse(false, 'Failed to get project info', null, 500);
        }

        return Common::apiResponse(true, 'Success', $result);
    }

    // ─── Credential (encrypted for mobile) ───────────────────

    public function credential(Request $request)
    {
        $streamData = self::streamData();

        return Common::apiResponse(true, 'Success', [
            'app_id' => $streamData['app_id'],
        ]);
    }

    // ─── WEBHOOKS - Event Handlers (16 Events) ───────────────

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-UTD-Stream-Signature');
        $rawBody = $request->getContent();

        if (!$signature || !$rawBody) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    // ─── Room Events (6) ─────────────────────────────────────

    public function onRoomStarted(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];

        Log::info('Webhook: room_started', [
            'room_name' => $room['name'] ?? null,
            'room_sid' => $room['sid'] ?? null,
        ]);

        // البحث عن صاحب الغرفة من جدول rooms
        $roomName = $room['name'] ?? null;
        $ownerUserId = null;

        if ($roomName) {
            // البحث في room_name أو numid
            $existingRoom = \DB::table('rooms')
                ->where('room_name', $roomName)
                ->orWhere('numid', $roomName)
                ->first();

            if ($existingRoom) {
                $ownerUserId = $existingRoom->uid;
            }
        }

        \App\Models\StreamingRoomSession::create([
            'room_name' => $room['name'] ?? null,
            'room_sid' => $room['sid'],
            'owner_user_id' => $ownerUserId,
            'started_at' => now(),
            'metadata' => $data,
        ]);

        // تحديث Analytics
        $analytics = \App\Models\StreamingAnalytics::today();
        $analytics->increment('total_sessions');

        // تحديث Cache للغرف النشطة
        $activeRooms = Cache::increment('streaming:active_rooms');
        if ($activeRooms > $analytics->peak_concurrent_rooms) {
            $analytics->update(['peak_concurrent_rooms' => $activeRooms]);
        }

        // إرسال إشعارات للمتابعين
        if ($ownerUserId) {
            dispatch(new \App\Jobs\SendNotificationToAllFollowers($ownerUserId))
                ->onQueue('notification_heavy');
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function onRoomFinished(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];

        Log::info('Webhook: room_finished', [
            'room_name' => $room['name'] ?? null,
            'room_sid' => $room['sid'] ?? null,
        ]);

        // تحديث نهاية الجلسة وحساب المدة
        $session = \App\Models\StreamingRoomSession::where('room_sid', $room['sid'])->first();
        if ($session) {
            $session->finished_at = now();
            $session->calculateDuration();

            // تحديث Analytics بمدة الجلسة
            if ($session->duration_minutes) {
                $analytics = \App\Models\StreamingAnalytics::today();
                $analytics->increment('total_session_minutes', $session->duration_minutes);
            }
        }

        // تقليل عدد الغرف النشطة
        Cache::decrement('streaming:active_rooms');

        return response()->json(['status' => 'ok'], 200);
    }

    public function onParticipantJoined(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];

        Log::info('Webhook: participant_joined', [
            'room_name' => $room['name'] ?? null,
            'participant' => $participant['identity'] ?? null,
        ]);

        // البحث عن الجلسة
        $roomSession = \App\Models\StreamingRoomSession::where('room_sid', $room['sid'])->first();

        if (!$roomSession) {
            Log::warning('Room session not found for participant_joined', ['room_sid' => $room['sid']]);
            return response()->json(['status' => 'ok'], 200);
        }

        // حفظ جلسة المشارك
        \App\Models\StreamingParticipantSession::create([
            'room_session_id' => $roomSession->id,
            'participant_identity' => $participant['identity'],
            'participant_name' => $participant['name'] ?? null,
            'joined_at' => now(),
        ]);

        // تحديث عدادات الغرفة
        $roomSession->increment('total_participants');

        $currentCount = $roomSession->participantSessions()->whereNull('left_at')->count();
        if ($currentCount > $roomSession->peak_participants) {
            $roomSession->update(['peak_participants' => $currentCount]);
        }

        // تحديث Analytics
        $analytics = \App\Models\StreamingAnalytics::today();
        $analytics->increment('total_participants');

        // تتبع المشاركين الفريدين
        $cacheKey = 'streaming:unique_participants:' . now()->toDateString();
        $uniqueParticipants = Cache::remember($cacheKey, now()->endOfDay(), function () {
            return collect();
        });

        if (!$uniqueParticipants->contains($participant['identity'])) {
            $uniqueParticipants->push($participant['identity']);
            Cache::put($cacheKey, $uniqueParticipants, now()->endOfDay());
            $analytics->increment('unique_participants');
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function onParticipantLeft(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];

        Log::info('Webhook: participant_left', [
            'room_name' => $room['name'] ?? null,
            'participant' => $participant['identity'] ?? null,
        ]);

        // البحث عن الجلسة
        $roomSession = \App\Models\StreamingRoomSession::where('room_sid', $room['sid'])->first();

        if (!$roomSession) {
            return response()->json(['status' => 'ok'], 200);
        }

        // تحديث وقت المغادرة وحساب المدة
        $participantSession = \App\Models\StreamingParticipantSession::where('room_session_id', $roomSession->id)
            ->where('participant_identity', $participant['identity'])
            ->whereNull('left_at')
            ->latest('joined_at')
            ->first();

        if ($participantSession) {
            $participantSession->left_at = now();
            $participantSession->calculateDuration();

            // تحديث Analytics بمدة المشارك
            if ($participantSession->duration_minutes) {
                $analytics = \App\Models\StreamingAnalytics::today();
                $analytics->increment('total_participant_minutes', $participantSession->duration_minutes);
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function onTrackPublished(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];
        $track = $data['track'] ?? [];

        Log::info('Webhook: track_published', [
            'room_name' => $room['name'] ?? null,
            'participant' => $participant['identity'] ?? null,
            'track_type' => $track['type'] ?? null,
        ]);

        // البحث عن الجلسة
        $roomSession = \App\Models\StreamingRoomSession::where('room_sid', $room['sid'])->first();

        if (!$roomSession) {
            return response()->json(['status' => 'ok'], 200);
        }

        // تحديد نوع وجودة الـ Track
        $trackType = strtolower($track['type'] ?? 'unknown');
        $videoQuality = null;

        if ($trackType === 'video') {
            $height = $track['height'] ?? 0;
            $videoQuality = \App\Models\StreamingTrack::determineVideoQuality($height);
        }

        // حفظ الـ Track
        \App\Models\StreamingTrack::create([
            'room_session_id' => $roomSession->id,
            'participant_identity' => $participant['identity'],
            'track_type' => $trackType,
            'video_quality' => $videoQuality,
            'video_width' => $track['width'] ?? null,
            'video_height' => $track['height'] ?? null,
            'published_at' => now(),
        ]);

        // تحديث Analytics حسب الجودة
        $analytics = \App\Models\StreamingAnalytics::today();

        if ($trackType === 'video') {
            match ($videoQuality) {
                'sd' => $analytics->increment('tracks_sd'),
                'hd' => $analytics->increment('tracks_hd'),
                'fhd' => $analytics->increment('tracks_fhd'),
                '2k' => $analytics->increment('tracks_2k'),
                '2k_plus', '4k' => $analytics->increment('tracks_2k_plus'),
                default => null,
            };
        } elseif ($trackType === 'audio') {
            $analytics->increment('tracks_audio');
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function onTrackUnpublished(Request $request)
    {
        $data = $request->all();
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];
        $track = $data['track'] ?? [];

        Log::info('Webhook: track_unpublished', [
            'room_name' => $room['name'] ?? null,
            'participant' => $participant['identity'] ?? null,
            'track_type' => $track['type'] ?? null,
        ]);

        // البحث عن الجلسة
        $roomSession = \App\Models\StreamingRoomSession::where('room_sid', $room['sid'])->first();

        if (!$roomSession) {
            return response()->json(['status' => 'ok'], 200);
        }

        // تحديث وقت إيقاف الـ Track وحساب المدة
        $trackType = strtolower($track['type'] ?? 'unknown');

        $trackRecord = \App\Models\StreamingTrack::where('room_session_id', $roomSession->id)
            ->where('participant_identity', $participant['identity'])
            ->where('track_type', $trackType)
            ->whereNull('unpublished_at')
            ->latest('published_at')
            ->first();

        if ($trackRecord) {
            $trackRecord->unpublished_at = now();
            $trackRecord->calculateDuration();
        }

        return response()->json(['status' => 'ok'], 200);
    }

    // ─── Call Events (7) ─────────────────────────────────────

    public function onCallInitiated(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_initiated', [
            'call_id' => $call['call_id'] ?? null,
            'caller' => $call['caller_identity'] ?? null,
            'callee' => $call['callee_identity'] ?? null,
            'type' => $call['type'] ?? 'voice',
        ]);

        // Cache call metadata
        Cache::put("stream:call:{$call['call_id']}", $call, now()->addDay());
        Cache::put("stream:call:{$call['call_id']}:initiated_at", now(), now()->addDay());

        // Update analytics
        Cache::increment('analytics:calls:total');
        Cache::increment('analytics:calls:active');

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallRinging(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_ringing', [
            'call_id' => $call['call_id'] ?? null,
        ]);

        Cache::put("stream:call:{$call['call_id']}:ringing_at", now(), now()->addDay());

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallAccepted(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_accepted', [
            'call_id' => $call['call_id'] ?? null,
        ]);

        Cache::put("stream:call:{$call['call_id']}:answered_at", now(), now()->addDay());
        Cache::increment('analytics:calls:accepted');

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallRejected(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_rejected', [
            'call_id' => $call['call_id'] ?? null,
        ]);

        Cache::decrement('analytics:calls:active');
        Cache::increment('analytics:calls:rejected');
        Cache::forget("stream:call:{$call['call_id']}");

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallBusy(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_busy', [
            'call_id' => $call['call_id'] ?? null,
        ]);

        Cache::decrement('analytics:calls:active');
        Cache::forget("stream:call:{$call['call_id']}");

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallEnded(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        $durationSeconds = $call['duration_seconds'] ?? 0;
        $durationMinutes = ceil($durationSeconds / 60);

        Log::info('Webhook: call_ended', [
            'call_id' => $call['call_id'] ?? null,
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
        ]);

        // Update usage statistics
        Cache::decrement('analytics:calls:active');

        $callType = $call['type'] ?? 'voice';
        if ($callType === 'video') {
            Cache::increment('analytics:calls:video_minutes', $durationMinutes);
        } else {
            Cache::increment('analytics:calls:audio_minutes', $durationMinutes);
        }

        Cache::forget("stream:call:{$call['call_id']}");

        return response()->json(['status' => 'ok'], 200);
    }

    public function onCallMissed(Request $request)
    {
        $data = $request->all();
        $call = $data['call'] ?? [];

        Log::info('Webhook: call_missed', [
            'call_id' => $call['call_id'] ?? null,
        ]);

        Cache::decrement('analytics:calls:active');
        Cache::increment('analytics:calls:missed');
        Cache::forget("stream:call:{$call['call_id']}");

        return response()->json(['status' => 'ok'], 200);
    }

    // ─── Presence Events (2) ─────────────────────────────────

    public function onUserOnline(Request $request)
    {
        $data = $request->all();
        $identity = $data['identity'] ?? null;
        $name = $data['name'] ?? null;

        Log::info('Webhook: user_online', [
            'identity' => $identity,
            'name' => $name,
        ]);

        if (!$identity || !is_numeric($identity)) {
            return response()->json(['status' => 'ok'], 200);
        }

        $userId = (int) $identity;

        // حفظ جلسة جديدة
        \App\Models\UserPresenceSession::create([
            'user_id' => $userId,
            'connected_at' => now(),
        ]);

        // Update cache للعدادات Live
        Cache::put("presence:user:{$userId}:status", 'online', now()->addHours(24));
        Cache::increment('analytics:presence:online_users');

        return response()->json(['status' => 'ok'], 200);
    }

    public function onUserOffline(Request $request)
    {
        $data = $request->all();
        $identity = $data['identity'] ?? null;
        $name = $data['name'] ?? null;

        Log::info('Webhook: user_offline', [
            'identity' => $identity,
            'name' => $name,
        ]);

        if (!$identity || !is_numeric($identity)) {
            return response()->json(['status' => 'ok'], 200);
        }

        $userId = (int) $identity;

        // إنهاء آخر جلسة مفتوحة وحساب المدة
        $session = \App\Models\UserPresenceSession::where('user_id', $userId)
            ->whereNull('disconnected_at')
            ->latest('connected_at')
            ->first();

        if ($session) {
            $session->disconnected_at = now();
            $session->calculateDuration();

            Log::info('User session ended', [
                'identity' => $identity,
                'duration_minutes' => $session->duration_minutes,
            ]);
        }

        // Update cache
        Cache::put("presence:user:{$userId}:status", 'offline', now()->addHours(24));
        Cache::decrement('analytics:presence:online_users');

        return response()->json(['status' => 'ok'], 200);
    }

    // ─── Messaging Events (1) ────────────────────────────────

    public function onMessageSent(Request $request)
    {
        $data = $request->all();
        $message = $data['message'] ?? [];

        Log::info('Webhook: message_sent', [
            'message_id' => $message['message_id'] ?? null,
            'conversation_id' => $message['conversation_id'] ?? null,
            'sender' => $message['sender_identity'] ?? null,
            'type' => $message['type'] ?? 'text',
        ]);

        // تحديث Analytics
        $analytics = \App\Models\StreamingAnalytics::today();
        $analytics->increment('total_messages');

        // TODO: إرسال push notification للمستقبل
        // يمكن إضافة هذا لاحقاً بناءً على conversation_id

        return response()->json(['status' => 'ok'], 200);
    }
}
