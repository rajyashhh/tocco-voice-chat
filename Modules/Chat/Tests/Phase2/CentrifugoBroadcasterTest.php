<?php

namespace Modules\Chat\Tests\Phase2;

use App\Broadcasting\Centrifugo\ChannelMapper;
use App\Broadcasting\CentrifugoBroadcaster;
use App\Broadcasting\CompositeBroadcaster;
use App\Broadcasting\SwitchableBroadcaster;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Phase 2 §6.6/§6.7 — Centrifugo HTTP publisher + the realtime_transport flag.
 *
 * No DB needed: the broadcaster is a pure HTTP seam. We fake the Centrifugo HTTP
 * API and assert exactly one publish/broadcast goes out, to the mapped channel,
 * with the {event,payload} envelope and the X-API-Key header — and that the
 * dual/pusher/centrifugo flag selects the right transport(s).
 */
class CentrifugoBroadcasterTest extends TestCase
{
    private const API_URL = 'http://centrifugo.test:8000/api';
    private const API_KEY = 'test-api-key-123';

    private function broadcaster(array $overrides = []): CentrifugoBroadcaster
    {
        $config = array_merge([
            'api_url' => self::API_URL,
            'api_key' => self::API_KEY,
            'timeout' => 3,
            'verify'  => false,
        ], $overrides);

        return new CentrifugoBroadcaster($config, new ChannelMapper());
    }

    private function fakeOk(): void
    {
        // Centrifugo returns HTTP 200 with an empty {result:{}} on success.
        Http::fake([
            self::API_URL . '/*' => Http::response(['result' => (object) []], 200),
        ]);
    }

    // ---------------------------------------------------------------------
    // single-channel publish: endpoint, header, mapped channel, envelope
    // ---------------------------------------------------------------------

    public function test_publish_targets_publish_endpoint_with_api_key_and_envelope(): void
    {
        $this->fakeOk();

        $this->broadcaster()->broadcast(['user-42'], 'getChatUsersBloc', ['message' => 'hi', 'id' => 7]);

        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();

            return $request->url() === self::API_URL . '/publish'
                && $request->method() === 'POST'
                && $request->hasHeader('X-API-Key', self::API_KEY)
                // user-42 -> user:#42 (ChannelMapper)
                && $body['channel'] === 'user:#42'
                // envelope preserves the broadcastAs event name + the payload as-is
                && $body['data']['event'] === 'getChatUsersBloc'
                && $body['data']['payload'] === ['message' => 'hi', 'id' => 7];
        });

        Http::assertSentCount(1);
    }

    public function test_conversation_channel_maps_to_dm_room_channel(): void
    {
        $this->fakeOk();

        $this->broadcaster()->broadcast(['conversation-15'], 'update-conversation-list', ['x' => 1]);

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === self::API_URL . '/publish'
                && $request->data()['channel'] === 'chat:dm.room.15';
        });
    }

    public function test_socket_key_is_stripped_from_payload(): void
    {
        $this->fakeOk();

        // Pusher injects `socket` for sender self-exclusion; Centrifugo must never
        // receive it.
        $this->broadcaster()->broadcast(['user-1'], 'evt', ['socket' => 'abc.def', 'keep' => true]);

        Http::assertSent(function (HttpRequest $request) {
            $payload = $request->data()['data']['payload'];

            return !array_key_exists('socket', $payload) && $payload === ['keep' => true];
        });
    }

    // ---------------------------------------------------------------------
    // multi-channel -> /broadcast (de-duplicated)
    // ---------------------------------------------------------------------

    public function test_multiple_distinct_channels_use_broadcast_endpoint(): void
    {
        $this->fakeOk();

        // user-1 -> user:#1, conversation-9 -> chat:dm.room.9 (two distinct targets)
        $this->broadcaster()->broadcast(['user-1', 'conversation-9'], 'open_chat', ['ok' => 1]);

        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();

            return $request->url() === self::API_URL . '/broadcast'
                && $body['channels'] === ['user:#1', 'chat:dm.room.9']
                && $body['data']['event'] === 'open_chat';
        });
        Http::assertSentCount(1);
    }

    public function test_duplicate_mapped_channels_collapse_to_single_publish(): void
    {
        $this->fakeOk();

        // user-5 and conversation-user5 both map to user:#5 -> one channel -> publish.
        $this->broadcaster()->broadcast(['user-5', 'conversation-user5'], 'evt', []);

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === self::API_URL . '/publish'
                && $request->data()['channel'] === 'user:#5';
        });
        Http::assertSentCount(1);
    }

    public function test_unmapped_channel_passes_through_unchanged(): void
    {
        $this->fakeOk();

        // Out-of-scope channels (group/presence/in-room) have no mapping rule, so
        // they stay pass-through unchanged — dual mode must not drop them.
        $this->broadcaster()->broadcast(['presence-room.42'], 'presence', ['g' => 1]);

        Http::assertSent(function (HttpRequest $request) {
            return $request->data()['channel'] === 'presence-room.42';
        });
    }

    // ---------------------------------------------------------------------
    // phase 9 WAVE 1: outside-room banners + per-user counters
    // ---------------------------------------------------------------------

    public function test_global_banner_channels_map_into_banner_namespace(): void
    {
        $this->fakeOk();

        // Each WAVE-1 global banner lands in the public `banner` namespace; the
        // broadcastAs event name is preserved verbatim in the envelope.
        $cases = [
            ['gift_banner', 'gift_banner', 'banner:gift'],
            ['super-lucky-box-chanel', 'superLuckBox', 'banner:lucky_box'],
            ['end.room.boom', 'end_room_boom', 'banner:boom'],
        ];

        foreach ($cases as [$legacy, $event, $expected]) {
            $this->broadcaster()->broadcast([$legacy], $event, ['x' => 1]);

            Http::assertSent(function (HttpRequest $request) use ($event, $expected) {
                $body = $request->data();

                return $request->url() === self::API_URL . '/publish'
                    && $body['channel'] === $expected
                    && $body['data']['event'] === $event
                    && $body['data']['payload'] === ['x' => 1];
            });
        }
    }

    public function test_per_user_counter_channels_map_to_user_limited_channel(): void
    {
        $this->fakeOk();

        // unread-{id} and status-user-{id} MUST land on user:#{id} (user-limited)
        // and NOWHERE else — a banner:* target would leak across users.
        $this->broadcaster()->broadcast(['unread-7'], 'UnreadCounterIndividual', ['type' => 'chat', 'counter' => 3]);
        Http::assertSent(fn (HttpRequest $r) => $r->data()['channel'] === 'user:#7');

        $this->broadcaster()->broadcast(['status-user-7'], 'status-user', ['can_play' => true]);
        Http::assertSent(fn (HttpRequest $r) => $r->data()['channel'] === 'user:#7');
    }

    public function test_wave2_banner_channels_map_into_banner_namespace(): void
    {
        $this->fakeOk();

        // These DO have a broadcaster publisher (App\Events\BannerEvent), so they
        // migrate into the public `banner` namespace; the broadcastAs event name
        // is preserved verbatim in the envelope.
        $cases = [
            ['win.lucky.gift.event', 'banner:lucky_gift'],
            ['game.win.event', 'banner:games'],
            ['room.comment.event', 'banner:comment'],
        ];

        foreach ($cases as [$legacy, $expected]) {
            $this->broadcaster()->broadcast([$legacy], $legacy, ['m' => 1]);

            Http::assertSent(function (HttpRequest $r) use ($legacy, $expected) {
                $body = $r->data();

                return $body['channel'] === $expected
                    && $body['data']['event'] === $legacy
                    && $body['data']['payload'] === ['m' => 1];
            });
        }
    }

    // ---------------------------------------------------------------------
    // safety: misconfig is a no-op, failures never throw
    // ---------------------------------------------------------------------

    public function test_missing_api_url_or_key_does_not_call_http(): void
    {
        $this->fakeOk();

        $this->broadcaster(['api_url' => '', 'api_key' => ''])
            ->broadcast(['user-1'], 'evt', []);

        Http::assertNothingSent();
    }

    public function test_http_failure_is_swallowed_and_logged(): void
    {
        // A connection error must NOT bubble out of broadcast() (plan §6.6).
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('boom');
        });

        Log::spy();

        $this->broadcaster()->broadcast(['user-1'], 'evt', []);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => $msg === 'CentrifugoBroadcaster.publish_failed')
            ->once();
    }

    public function test_centrifugo_logical_error_is_logged_without_throwing(): void
    {
        // HTTP 200 but an `error` object in the body = logical failure.
        Http::fake([
            self::API_URL . '/*' => Http::response(['error' => ['code' => 102, 'message' => 'namespace not found']], 200),
        ]);

        Log::spy();

        $this->broadcaster()->broadcast(['user-1'], 'evt', []);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => $msg === 'CentrifugoBroadcaster.api_error')
            ->once();
    }

    // ---------------------------------------------------------------------
    // realtime_transport flag: pusher | dual | centrifugo
    // ---------------------------------------------------------------------

    public function test_boot_wraps_pusher_default_with_switchable(): void
    {
        // Production runs on the 'pusher' default; boot() transparently wraps it
        // with 'switchable' (the live runtime toggle). The realtime_transport
        // flag is then read per-broadcast, not chosen at boot.
        config(['broadcasting.default' => 'pusher']);
        config(['broadcasting.realtime_transport' => 'pusher']);

        // boot() requires routes/channels.php, which resolves the switchable
        // default -> builds the Pusher leg. Provide dummy creds so the Pusher SDK
        // constructs exactly as in prod; nothing is published here.
        putenv('PUSHER_APP_ID=test-app-id');
        putenv('PUSHER_APP_KEY=test-app-key');
        putenv('PUSHER_APP_SECRET=test-app-secret');
        putenv('PUSHER_APP_CLUSTER=mt1');

        try {
            (new \App\Providers\BroadcastServiceProvider($this->app))->boot();

            $this->assertSame('switchable', config('broadcasting.default'));
        } finally {
            putenv('PUSHER_APP_ID');
            putenv('PUSHER_APP_KEY');
            putenv('PUSHER_APP_SECRET');
            putenv('PUSHER_APP_CLUSTER');
        }
    }

    public function test_switchable_pusher_routes_primary_only(): void
    {
        Cache::flush();
        config(['broadcasting.realtime_transport' => 'pusher']);

        $primary = new RecordingBroadcaster();
        $secondary = new RecordingBroadcaster();
        (new SwitchableBroadcaster($primary, $secondary))
            ->broadcast(['chat:dm.1_2'], 'evt', ['a' => 1]);

        $this->assertCount(1, $primary->calls);   // Pusher only — identical to today
        $this->assertCount(0, $secondary->calls); // Centrifugo never touched
    }

    public function test_switchable_dual_routes_both(): void
    {
        Cache::flush();
        config(['broadcasting.realtime_transport' => 'dual']);

        $primary = new RecordingBroadcaster();
        $secondary = new RecordingBroadcaster();
        (new SwitchableBroadcaster($primary, $secondary))
            ->broadcast(['chat:dm.1_2'], 'evt', ['a' => 1]);

        $this->assertCount(1, $primary->calls);
        $this->assertCount(1, $secondary->calls);
    }

    public function test_switchable_centrifugo_routes_secondary_only(): void
    {
        Cache::flush();
        config(['broadcasting.realtime_transport' => 'centrifugo']);

        $primary = new RecordingBroadcaster();
        $secondary = new RecordingBroadcaster();
        (new SwitchableBroadcaster($primary, $secondary))
            ->broadcast(['chat:dm.1_2'], 'evt', ['a' => 1]);

        $this->assertCount(0, $primary->calls);   // Pusher not used in final state
        $this->assertCount(1, $secondary->calls); // Centrifugo only
    }

    public function test_dual_publishes_to_both_primary_and_secondary(): void
    {
        $this->fakeOk();

        $primary = new RecordingBroadcaster();
        $secondary = $this->broadcaster();

        $composite = new CompositeBroadcaster($primary, $secondary);
        $composite->broadcast(['user-42'], 'evt', ['a' => 1]);

        // Primary (Pusher stand-in) received the event verbatim, original channel.
        $this->assertCount(1, $primary->calls);
        $this->assertSame(['user-42'], $primary->calls[0]['channels']);
        $this->assertSame('evt', $primary->calls[0]['event']);

        // Secondary (Centrifugo) also published, mapped channel.
        Http::assertSent(fn (HttpRequest $r) => $r->data()['channel'] === 'user:#42');
        Http::assertSentCount(1);
    }

    public function test_dual_only_calls_primary_when_secondary_maps_nothing(): void
    {
        $this->fakeOk();

        $primary = new RecordingBroadcaster();
        // An empty channel maps to [] in ChannelMapper -> Centrifugo no-op.
        $composite = new CompositeBroadcaster($primary, $this->broadcaster());
        $composite->broadcast([''], 'evt', []);

        $this->assertCount(1, $primary->calls, 'primary always fires');
        Http::assertNothingSent();
    }

    public function test_dual_primary_failure_propagates_pusher_semantics_unchanged(): void
    {
        $this->fakeOk();

        $primary = new ThrowingBroadcaster();
        $composite = new CompositeBroadcaster($primary, $this->broadcaster());

        // The primary (Pusher) leg must keep its exact error semantics: its
        // exception propagates, it is NOT swallowed by the composite.
        $this->expectException(\RuntimeException::class);
        $composite->broadcast(['user-1'], 'evt', []);
    }

    public function test_dual_secondary_failure_never_affects_primary(): void
    {
        // Centrifugo leg throws hard; primary must still have run and the composite
        // must not rethrow (secondary is isolated, plan §9).
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('down');
        });
        Log::spy();

        $primary = new RecordingBroadcaster();
        $composite = new CompositeBroadcaster($primary, $this->broadcaster());

        $composite->broadcast(['user-1'], 'evt', []);

        $this->assertCount(1, $primary->calls, 'primary leg ran despite secondary failure');
    }
}

/**
 * Minimal Broadcaster stand-in that records broadcast() calls — represents the
 * proven Pusher primary in the composite without touching the real Pusher SDK.
 */
class RecordingBroadcaster implements BroadcasterContract
{
    public array $calls = [];

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->calls[] = compact('channels', 'event', 'payload');
    }

    public function auth($request) { return null; }
    public function validAuthenticationResponse($request, $result) { return $result; }
    public function channel($channel, $callback, $options = []) { return $this; }
}

/**
 * Primary stand-in that throws — proves the composite does NOT swallow the
 * primary (Pusher) leg's exceptions.
 */
class ThrowingBroadcaster implements BroadcasterContract
{
    public function broadcast(array $channels, $event, array $payload = [])
    {
        throw new \RuntimeException('primary failed');
    }

    public function auth($request) { return null; }
    public function validAuthenticationResponse($request, $result) { return $result; }
    public function channel($channel, $callback, $options = []) { return $this; }
}
