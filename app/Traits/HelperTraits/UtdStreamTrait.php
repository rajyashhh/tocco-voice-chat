<?php

namespace App\Traits\HelperTraits;

use App\Helpers\Common;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait UtdStreamTrait
{
    // Direct backend endpoint (bypasses the CDN/proxy layer that was dropping
    // ~0.75-2% of concurrent connections → cURL error 28 with 0 bytes).
    // Verified 2026-06-11: 2000 reqs x50 concurrent from the jo VM = 0 failures
    // vs 15 on the proxied apex domain.
    //
    // Resolution order (highest → lowest):
    //   1. runtime DB override  `utd_stream_base_url`
    //   2. config/env           `services.utd_stream.base_url` (UTD_STREAM_BASE_URL)
    // The config default IS the production url, so prod is unchanged; a clone
    // (test / Meow Live) points elsewhere via env without touching code.

    private const STREAM_CB_FAILURES_KEY = 'utd_stream_cb_failures';
    private const STREAM_CB_OPEN_KEY = 'utd_stream_cb_open';
    private const STREAM_CB_WAS_OPEN_KEY = 'utd_stream_cb_was_open';
    private const STREAM_CB_THRESHOLD = 5;
    private const STREAM_CB_OPEN_SECONDS = 60;

    private static function streamBaseUrl()
    {
        return Common::getConfig('utd_stream_base_url')
            ?: config('services.utd_stream.base_url', 'https://engine.udt-stream.com/api/v1');
    }

    public static function streamData($key = null)
    {
        $data = [
            'app_id' => Common::getConfig('utd_stream_app_id') ?? '',
            'server_secret' => Common::getConfig('utd_stream_server_secret') ?? '',
        ];

        if ($key) {
            return $data[$key] ?? null;
        }

        return $data;
    }

    private static function streamRequest($method, $path, $data = [], int $retryTimes = 0, ?int $timeoutSeconds = null)
    {
        $appId = self::streamData('app_id');
        $secret = self::streamData('server_secret');

        $headers = [
            'X-App-Id' => $appId,
            'X-App-Secret' => $secret,
        ];

        if (Cache::get(self::STREAM_CB_OPEN_KEY)) {
            return null;
        }

        $url = self::streamBaseUrl() . $path;
        $timeout = $timeoutSeconds ?? (int) config('utd_stream.timeout_seconds', 10);

        try {
            $request = Http::withHeaders($headers)->acceptJson()->timeout($timeout);

            if ($retryTimes > 0) {
                // Retry transport-level failures only (never HTTP error responses),
                // with a short fixed backoff. throw=false keeps the non-2xx behaviour
                // identical to the non-retry path (response is returned, not thrown).
                $request = $request->retry(
                    $retryTimes + 1,
                    (int) config('utd_stream.retry_backoff_ms', 150),
                    function ($exception) {
                        if (! $exception instanceof ConnectionException) {
                            return false;
                        }

                        // Count every failed attempt — not just the final failure —
                        // so the breaker opens fast under a full outage. (The final
                        // attempt is counted by the catch below; this callback is
                        // only invoked for attempts that will be retried.)
                        self::recordStreamFailure();

                        return true;
                    },
                    false
                );
            }

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => $request->post($url, $data),
            };

            Cache::forget(self::STREAM_CB_FAILURES_KEY);

            if (Cache::pull(self::STREAM_CB_WAS_OPEN_KEY)) {
                Log::info('UTD-STREAM circuit breaker closed', ['path' => $path]);
            }

            return $response->json();
        } catch (ConnectionException $e) {
            self::recordStreamFailure();

            Log::error('UTD-STREAM API error', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('UTD-STREAM API error', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private static function recordStreamFailure()
    {
        $failures = (int) Cache::get(self::STREAM_CB_FAILURES_KEY, 0) + 1;

        if ($failures >= self::STREAM_CB_THRESHOLD) {
            Cache::put(self::STREAM_CB_OPEN_KEY, true, self::STREAM_CB_OPEN_SECONDS);
            Cache::put(self::STREAM_CB_WAS_OPEN_KEY, true, self::STREAM_CB_OPEN_SECONDS * 5);
            Cache::forget(self::STREAM_CB_FAILURES_KEY);

            Log::warning('UTD-STREAM circuit breaker OPENED', [
                'failures' => $failures,
                'open_seconds' => self::STREAM_CB_OPEN_SECONDS,
            ]);

            return;
        }

        Cache::put(self::STREAM_CB_FAILURES_KEY, $failures, self::STREAM_CB_OPEN_SECONDS);
    }

    // ─── Token ───────────────────────────────────────────────

    public static function generateStreamToken($identity, $roomName, $name = null, $role = null, $service = null, array $extra = [])
    {
        $body = [
            'identity' => $identity,
            'room_name' => $roomName,
        ];

        if ($name) $body['name'] = $name;
        if ($role) $body['role'] = $role;
        if ($service) $body['service'] = $service;

        // Optional engine hints (server-signed client flow): room_owner_id +
        // host-only seat layout params (seat_count/seat_mode/host_seat/mode_id)
        // + device context (os/device_model/os_version/app_version) — `os` is
        // required by the engine to grant the videoEffects (beauty filters)
        // entitlement; it fails closed without it.
        foreach ($extra as $key => $value) {
            if ($value !== null && $value !== '') {
                $body[$key] = $value;
            }
        }

        return self::streamRequest('POST', '/token', $body);
    }

    // ─── Rooms ───────────────────────────────────────────────

    public static function listRooms()
    {
        // The engine paginates GET /rooms (default 20 per page, max 100,
        // newest first). A single unpaged call therefore returned only the 20
        // NEWEST active rooms — the occupancy sync treated every older
        // occupied room as stale and deactivated it (occupied rooms vanished
        // from the home list and their visitor counts zeroed, 2026-06-11).
        // Walk every page and hand callers the FULL flat list.
        $all = [];
        $page = 1;
        $totalPages = 1;

        do {
            // Params MUST go through $data: streamRequest passes $data as the
            // HTTP client's query option, and Guzzle's `query` REPLACES any
            // query string already in the URL — an inline ?per_page=&page=
            // was silently stripped (page 1 fetched twice, oldest rooms never
            // seen, room 311 wrongly deactivated — 2026-06-11).
            $resp = self::streamRequest('GET', '/rooms', [
                'per_page' => 100,
                'page' => $page,
            ]);
            if (!is_array($resp)) {
                // Transport failure mid-walk: surface null on the FIRST page
                // (callers must not treat it as "no rooms"), or stop with what
                // we have on later pages rather than dropping earlier rooms.
                return $page === 1 ? $resp : $all;
            }

            $data = $resp['data'] ?? $resp;
            if (!is_array($data)) {
                return $page === 1 ? $resp : $all;
            }
            $all = array_merge($all, array_values(array_filter($data, 'is_array')));

            $pagination = $resp['pagination'] ?? null;
            $totalPages = is_array($pagination)
                ? max(1, (int) ($pagination['total_pages'] ?? 1))
                : 1;
            $page++;
        } while ($page <= $totalPages && $page <= 50);

        return $all;
    }

    public static function getRoomInfo($roomName)
    {
        return self::streamRequest('GET', '/rooms/' . urlencode($roomName));
    }

    public static function closeRoom($roomName)
    {
        return self::streamRequest('DELETE', '/rooms/' . urlencode($roomName));
    }

    public static function updateRoomMetadata($roomName, $metadata)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/metadata', [
            'metadata' => $metadata,
        ]);
    }

    // ─── Send Data ───────────────────────────────────────────
    // streamSendData is the low-level engine call; the Common::sendToStream*
    // shims (RoomDataTrait) queue into it via PushStreamDataJob. The name must
    // stay distinct from the shim: Common composes both traits, and an
    // identical method name would be a trait collision.

    public static function streamSendData($roomName, $data, $destinationIdentities = null)
    {
        $body = ['data' => $data];

        if ($destinationIdentities) {
            $body['destination_identities'] = (array)$destinationIdentities;
        }

        return self::streamRequest(
            'POST',
            '/rooms/' . urlencode($roomName) . '/send-data',
            $body,
            (int) config('utd_stream.send_data_retries', 1),
            (int) config('utd_stream.send_data_timeout_seconds', 6)
        );
    }

    public static function sendToStreamUser($roomName, $toIdentity, $data)
    {
        return self::streamSendData($roomName, $data, [$toIdentity]);
    }

    // ─── Participants ────────────────────────────────────────

    public static function getParticipant($roomName, $identity)
    {
        return self::streamRequest('GET', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity));
    }

    public static function kickUser($roomName, $identity)
    {
        return self::streamRequest('DELETE', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity));
    }

    public static function muteUser($roomName, $identity, $audio = true, $video = false)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity) . '/mute', [
            'audio' => $audio,
            'video' => $video,
        ]);
    }

    public static function updatePermissions($roomName, $identity, $permissions)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity) . '/permissions', $permissions);
    }

    public static function updateParticipantMetadata($roomName, $identity, $metadata)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity) . '/metadata', [
            'metadata' => $metadata,
        ]);
    }

    public static function forbidStream($roomName, $identity)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity) . '/forbid-stream');
    }

    public static function resumeStream($roomName, $identity)
    {
        return self::streamRequest('PUT', '/rooms/' . urlencode($roomName) . '/participants/' . urlencode($identity) . '/resume-stream');
    }

    // ─── Calls ───────────────────────────────────────────────

    public static function initiateCall($callerIdentity, $calleeIdentity, $type = 'voice', $metadata = null)
    {
        $body = [
            'caller_identity' => $callerIdentity,
            'callee_identity' => $calleeIdentity,
            'type' => $type,
        ];

        if ($metadata) $body['metadata'] = $metadata;

        return self::streamRequest('POST', '/calls', $body);
    }

    public static function callRinging($callId, $identity)
    {
        return self::streamRequest('POST', '/calls/' . urlencode($callId) . '/ringing', [
            'identity' => $identity,
        ]);
    }

    public static function acceptCall($callId, $identity)
    {
        return self::streamRequest('POST', '/calls/' . urlencode($callId) . '/accept', [
            'identity' => $identity,
        ]);
    }

    public static function rejectCall($callId, $identity)
    {
        return self::streamRequest('POST', '/calls/' . urlencode($callId) . '/reject', [
            'identity' => $identity,
        ]);
    }

    public static function callBusy($callId, $identity)
    {
        return self::streamRequest('POST', '/calls/' . urlencode($callId) . '/busy', [
            'identity' => $identity,
        ]);
    }

    public static function endCall($callId, $identity)
    {
        return self::streamRequest('POST', '/calls/' . urlencode($callId) . '/end', [
            'identity' => $identity,
        ]);
    }

    public static function getCall($callId)
    {
        return self::streamRequest('GET', '/calls/' . urlencode($callId));
    }

    public static function listCalls($filters = [])
    {
        return self::streamRequest('GET', '/calls', $filters);
    }

    // ─── Bans ────────────────────────────────────────────────

    public static function banUser($identity, $roomName = null, $reason = null, $duration = null)
    {
        $body = ['identity' => $identity];

        if ($roomName) $body['room_name'] = $roomName;
        if ($reason) $body['reason'] = $reason;
        if ($duration) $body['duration'] = $duration;

        return self::streamRequest('POST', '/rooms/ban', $body);
    }

    public static function unbanUser($identity, $roomName = null)
    {
        $body = ['identity' => $identity];

        if ($roomName) $body['room_name'] = $roomName;

        return self::streamRequest('DELETE', '/rooms/ban', $body);
    }

    public static function listBans()
    {
        return self::streamRequest('GET', '/rooms/bans');
    }

    // ─── Project Info ────────────────────────────────────────

    public static function getProjectInfo()
    {
        return self::streamRequest('GET', '/project');
    }
}
