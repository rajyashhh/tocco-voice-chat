<?php

namespace App\Broadcasting;

use App\Broadcasting\Centrifugo\ChannelMapper;
use App\Jobs\RepublishCentrifugoPublication;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Publishes broadcast events to Centrifugo over its HTTP API.
 *
 * REALTIME_CHAT_REBUILD_PLAN sections 6.6 + 6.7. This is the "outside-the-room"
 * transport that replaces Pusher. It receives the legacy Pusher channel names
 * from the event layer (broadcastOn), translates them through ChannelMapper, and
 * POSTs to {api_url}/publish (or /broadcast for multiple channels) with the
 * X-API-Key header.
 *
 * Failure policy (plan 6.6): a publish failure MUST NOT break the HTTP request
 * that triggered the broadcast. send() retries transport failures (cURL 7/28) in
 * place, then surfaces ANY final failure — exhausted transport error, HTTP non-2xx
 * (5xx caddy OOM / node restart, the 2026-06-12 signature), or HTTP 200 with a
 * logical `{"error":{...}}` envelope — as a CentrifugoPublishException. broadcast()
 * catches it so the request is never broken; for chat/banner channels the failure
 * is then effectively swallowed (the client-side offline-sync layer — drift + REST
 * gap-fill, phases 4-5 — is the safety net there). Personal `user:#{id}` channels
 * have NO such safety net (a publication that never reached the node cannot be
 * recovered from channel history), so broadcast() requeues exactly that subset with
 * backoff via RepublishCentrifugoPublication instead of dropping it (256 publishes
 * lost in the 2026-06-12 01:00 UTC node outage, 69 on user:# channels).
 */
class CentrifugoBroadcaster extends Broadcaster
{
    public function __construct(
        protected array $config,
        protected ChannelMapper $mapper,
    ) {
    }

    /**
     * Authenticate the incoming subscription request for a given channel.
     *
     * Channel subscription authorization for Centrifugo is handled by the
     * dedicated token / subscribe-proxy endpoints (plan section 4.2, built by the
     * auth agent in phase 3), not by the legacy /broadcasting/auth route. During
     * the dual-transport window the legacy auth route stays bound to the Pusher
     * driver via the composite broadcaster, so this method is only reached if the
     * default transport is `centrifugo`. We still verify the caller against the
     * registered channel authorizers to avoid an open auth endpoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if (empty($request->channel_name) || ! $this->retrieveUser($request, $channelName)) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel($request, $channelName);
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return is_bool($result) ? json_encode($result) : $result;
    }

    /**
     * Broadcast the given event to Centrifugo.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        // `socket` is a Pusher-only concept (sender self-exclusion). Centrifugo
        // does not use it; drop it so it never leaks into the published payload.
        Arr::pull($payload, 'socket');

        $legacyChannels = $this->formatChannels($channels);

        $targetChannels = $this->mapper->mapMany($legacyChannels);

        if (empty($targetChannels)) {
            return;
        }

        // Centrifugo data envelope: the event name is preserved so the client can
        // dispatch on it exactly like the Pusher `broadcastAs` name.
        $data = [
            'event' => $event,
            'payload' => $payload,
        ];

        try {
            $this->publishNow($targetChannels, $data);
        } catch (\Throwable $e) {
            // Never break the request that triggered the broadcast (plan 6.6).
            Log::error('CentrifugoBroadcaster.publish_failed', [
                'event' => $event,
                'channels' => $targetChannels,
                'error' => $e->getMessage(),
            ]);

            $this->requeuePersonalChannels($targetChannels, $data, $event);
        }
    }

    /**
     * Publish to the given channels, throwing on transport failure (after the
     * in-place retry in send()). Shared by broadcast() and the republish job.
     */
    public function publishNow(array $channels, array $data): void
    {
        if (count($channels) === 1) {
            $this->publish($channels[0], $data);
        } else {
            $this->publishMany($channels, $data);
        }
    }

    /**
     * Requeue the personal (`user:#{id}`) subset of a failed publication for a
     * backoff retry — the only channel class with no client-side recovery path.
     */
    protected function requeuePersonalChannels(array $channels, array $data, string $event): void
    {
        $prefix = (string) config('centrifugo.channels.user_prefix', 'user:#');

        $personal = array_values(array_filter(
            $channels,
            fn ($channel) => str_starts_with((string) $channel, $prefix)
        ));

        if (empty($personal)) {
            return;
        }

        try {
            dispatch(new RepublishCentrifugoPublication($personal, $data));
        } catch (\Throwable $e) {
            Log::error('CentrifugoBroadcaster.requeue_failed', [
                'event' => $event,
                'channels' => $personal,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish to a single channel via the Centrifugo `publish` API.
     */
    protected function publish(string $channel, array $data): void
    {
        $this->send('publish', [
            'channel' => $channel,
            'data' => $data,
        ]);
    }

    /**
     * Publish the same data to many channels via the Centrifugo `broadcast` API.
     */
    protected function publishMany(array $channels, array $data): void
    {
        $this->send('broadcast', [
            'channels' => array_values($channels),
            'data' => $data,
        ]);
    }

    /**
     * Send a command to the Centrifugo HTTP API.
     *
     * Centrifugo v6 accepts per-method endpoints ({api_url}/publish,
     * {api_url}/broadcast) authenticated with the `X-API-Key` header. Throws
     * CentrifugoPublishException on any final failure (see the failure policy in
     * the class docblock); a missing api_url/api_key is a no-op warning, never a
     * throw (an unconfigured transport must not break requests or spin the queue).
     */
    protected function send(string $method, array $command): void
    {
        $apiUrl = rtrim((string) ($this->config['api_url'] ?? ''), '/');
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiUrl === '' || $apiKey === '') {
            Log::warning('CentrifugoBroadcaster.misconfigured', [
                'reason' => 'CENTRIFUGO_API_URL or CENTRIFUGO_API_KEY is not set',
                'method' => $method,
            ]);

            return;
        }

        $timeout = (float) ($this->config['timeout'] ?? 3);
        $verify = (bool) ($this->config['verify'] ?? true);
        $retries = (int) ($this->config['retries'] ?? 1);
        $backoffMs = (int) ($this->config['retry_backoff_ms'] ?? 200);

        $request = Http::withHeaders(['X-API-Key' => $apiKey])
            ->timeout($timeout)
            ->withOptions(['verify' => $verify])
            ->acceptJson()
            ->asJson();

        if ($retries > 0) {
            // Retry transport-level failures only (cURL 7 refused / 28 timeout —
            // the two signatures of the node outage), never HTTP error responses.
            // throw=false keeps non-2xx flowing to the api_error log below;
            // exhausted ConnectionExceptions still throw to the caller.
            $request = $request->retry(
                $retries + 1,
                $backoffMs,
                fn ($exception) => $exception instanceof ConnectionException,
                false
            );
        }

        $response = $request->post($apiUrl . '/' . $method, $command);

        // A publish can fail two ways AFTER the transport succeeded: an HTTP
        // non-2xx (5xx from a caddy OOM / node restart — the 2026-06-12 outage
        // signature) or an HTTP 200 carrying Centrifugo's logical `{"error":{...}}`
        // envelope (node internal error). Both were previously logged-and-swallowed,
        // which silently disarmed the personal-channel safety net (publishNow then
        // returned normally, broadcast()'s catch never fired, the republish job was
        // never dispatched). Log it, then THROW so the failure reaches the caller:
        // broadcast() requeues the user:# subset and RepublishCentrifugoPublication
        // genuinely retries. Transport retries (cURL 7/28) already happened above.
        if ($response->failed() || $response->json('error') !== null) {
            Log::error('CentrifugoBroadcaster.api_error', [
                'method' => $method,
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            throw new CentrifugoPublishException(sprintf(
                'Centrifugo %s failed: status=%d error=%s',
                $method,
                $response->status(),
                json_encode($response->json('error'))
            ));
        }
    }

    /**
     * Normalize the channel name for authorization lookups.
     *
     * Legacy Pusher prefixes are stripped so the name matches the patterns
     * registered in routes/channels.php.
     */
    protected function normalizeChannelName($channel): string
    {
        foreach (['private-encrypted-', 'private-', 'presence-'] as $prefix) {
            if (str_starts_with((string) $channel, $prefix)) {
                return substr((string) $channel, strlen($prefix));
            }
        }

        return (string) $channel;
    }
}
