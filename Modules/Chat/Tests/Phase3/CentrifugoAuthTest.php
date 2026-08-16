<?php

namespace Modules\Chat\Tests\Phase3;

use App\Http\Controllers\Api\V1\CentrifugoAuthController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Phase 3 §4.2 — Centrifugo auth surface (controller behaviour).
 *
 *  (a) /centrifugo/token        : connection JWT, HS256, sub/exp/info claims.
 *  (b) /centrifugo/subscription : 1:1 subscription token; channel claim matches
 *                                 chat:dm.{min}_{max}; issued with or without a
 *                                 pre-existing room; blocked pair/self -> 403,
 *                                 unknown peer -> 422.
 *  (c) /centrifugo/subscribe    : group subscribe proxy; active member -> result{},
 *                                 non/banned member -> permission-denied envelope.
 */
class CentrifugoAuthTest extends Phase3AuthTestCase
{
    private function controller(): CentrifugoAuthController
    {
        return new CentrifugoAuthController();
    }

    private function claims(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->hmacSecret, 'HS256'));
    }

    // ---------------------------------------------------------------------
    // (a) connection token
    // ---------------------------------------------------------------------

    public function test_token_issues_signed_connection_jwt_with_expected_claims(): void
    {
        $this->seedUser(42);

        $response = $this->controller()->token(
            $this->requestAs(42, [], 'Sara', 'avatars/42.png')
        );
        $body = $this->decode($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);

        $claims = $this->claims($body['token']);
        $this->assertSame('42', $claims['sub'], 'sub must be the string user id');
        $this->assertIsString($claims['sub']);
        $this->assertGreaterThan(time(), $claims['exp']);
        $this->assertSame(3600, $claims['exp'] - $claims['iat']);

        $info = (array) $claims['info'];
        $this->assertSame('Sara', $info['name']);
        $this->assertSame('avatars/42.png', $info['avatar']);
    }

    public function test_token_returns_503_when_hmac_secret_missing(): void
    {
        config(['centrifugo.hmac_secret' => '']);
        $this->seedUser(1);

        $response = $this->controller()->token($this->requestAs(1));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertFalse($this->decode($response)['success']);
    }

    public function test_token_auto_subscribes_global_banner_channels(): void
    {
        // Phase 9 WAVE 1 + WAVE 2: the connection JWT carries a `channels` claim
        // that auto-subscribes the fixed, public-safe banner set at connect — no
        // per-channel tokens. Per-user channels are NOT listed (client-created,
        // authorized by `sub`).
        $this->seedUser(42);

        $response = $this->controller()->token($this->requestAs(42));
        $claims = $this->claims($this->decode($response)['token']);

        $this->assertArrayHasKey('channels', $claims);
        $this->assertSame(
            [
                'banner:gift', 'banner:lucky_box', 'banner:boom',
                'banner:lucky_gift', 'banner:games', 'banner:comment',
                'banner:boom_rewards',
            ],
            (array) $claims['channels']
        );

        foreach ((array) $claims['channels'] as $channel) {
            $this->assertStringStartsWith('banner:', $channel, 'no private/user channel may be auto-subscribed');
        }
    }

    // ---------------------------------------------------------------------
    // (b) subscription token (1:1)
    // ---------------------------------------------------------------------

    public function test_subscription_issues_token_for_a_party_with_canonical_channel(): void
    {
        $this->seedUser(77);
        $this->seedUser(42);
        $room = $this->makeRoom(77, 42);
        $this->addMember($room, 77);
        $this->addMember($room, 42);

        // Caller 77 requests a token for the DM with 42.
        $response = $this->controller()->subscription($this->requestAs(77, ['user_id' => 42]));
        $body = $this->decode($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        // Order-independent canonical name: min_max regardless of caller order.
        $this->assertSame('chat:dm.42_77', $body['channel']);

        $claims = $this->claims($body['token']);
        $this->assertSame('77', $claims['sub']);
        $this->assertSame('chat:dm.42_77', $claims['channel'], 'channel claim must match the channel exactly');
    }

    public function test_subscription_channel_is_identical_for_both_participants(): void
    {
        $this->seedUser(77);
        $this->seedUser(42);
        $room = $this->makeRoom(77, 42);
        $this->addMember($room, 77);
        $this->addMember($room, 42);

        $a = $this->decode($this->controller()->subscription($this->requestAs(77, ['user_id' => 42])));
        $b = $this->decode($this->controller()->subscription($this->requestAs(42, ['user_id' => 77])));

        $this->assertSame($a['channel'], $b['channel'], 'both parties resolve the same DM channel');
        $this->assertSame('chat:dm.42_77', $a['channel']);
    }

    public function test_subscription_works_for_legacy_room_without_membership_rows(): void
    {
        // Legacy 1:1 room: participant columns set, NOT backfilled into members.
        $this->seedUser(5);
        $this->seedUser(9);
        $this->makeRoom(5, 9);

        $response = $this->controller()->subscription($this->requestAs(5, ['user_id' => 9]));
        $body = $this->decode($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('chat:dm.5_9', $body['channel']);
    }

    public function test_subscription_issues_token_for_brand_new_conversation(): void
    {
        // No ChatRoom/membership rows exist between 3 and 1 yet (chat opened
        // from a profile/room BEFORE the first message — the most common DM
        // entry). The channel is derived server-side from (caller, peer), so
        // the caller is a party by construction and the token must issue;
        // the old room-existence gate 403'd every new conversation and left
        // the SDK retry-looping (2,248x403/24h in production, 2026-06-12).
        $this->seedUser(1);
        $this->seedUser(3);

        $response = $this->controller()->subscription($this->requestAs(3, ['user_id' => 1]));
        $body = $this->decode($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame('chat:dm.1_3', $body['channel']);

        $claims = $this->claims($body['token']);
        $this->assertSame('3', $claims['sub']);
        $this->assertSame('chat:dm.1_3', $claims['channel']);
    }

    public function test_subscription_rejects_blocked_pair_with_403(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->blockBetween(1, 2);

        // Both directions refuse: the block is pair-wise.
        $a = $this->controller()->subscription($this->requestAs(1, ['user_id' => 2]));
        $b = $this->controller()->subscription($this->requestAs(2, ['user_id' => 1]));

        $this->assertSame(403, $a->getStatusCode());
        $this->assertFalse($this->decode($a)['success']);
        $this->assertSame(403, $b->getStatusCode());
    }

    public function test_subscription_rejects_unknown_peer_with_422(): void
    {
        $this->seedUser(1);

        $response = $this->controller()->subscription($this->requestAs(1, ['user_id' => 999999]));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($this->decode($response)['success']);
    }

    public function test_subscription_rejects_self_dm(): void
    {
        $this->seedUser(1);

        $response = $this->controller()->subscription($this->requestAs(1, ['user_id' => 1]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_subscription_validation_requires_user_id(): void
    {
        // subscription() validates via Validator::make and answers 422 JSON
        // itself instead of throwing ValidationException (see controller doc).
        $this->seedUser(1);

        $response = $this->controller()->subscription($this->requestAs(1, []));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($this->decode($response)['success']);
    }

    // ---------------------------------------------------------------------
    // (c) subscribe proxy (groups)
    // ---------------------------------------------------------------------

    public function test_subscribe_grants_active_group_member(): void
    {
        $this->seedUser(10);
        $room = $this->makeRoom(10, null, 'group');
        $this->addMember($room, 10, ['role' => 'owner']);

        $request = $this->requestAs(0, [
            'user'    => '10',
            'channel' => "groups:room.{$room}",
            'client'  => 'abc-123',
        ]);

        $response = $this->controller()->subscribe($request);
        $body = $this->decode($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayNotHasKey('error', $body);
    }

    public function test_subscribe_denies_non_member(): void
    {
        $this->seedUser(10);
        $this->seedUser(11);
        $room = $this->makeRoom(10, null, 'group');
        $this->addMember($room, 10, ['role' => 'owner']);

        $request = $this->requestAs(0, [
            'user'    => '11',
            'channel' => "groups:room.{$room}",
            'client'  => 'abc-123',
        ]);

        $body = $this->decode($this->controller()->subscribe($request));

        $this->assertArrayHasKey('error', $body);
        $this->assertSame(403, $body['error']['code']);
        $this->assertArrayNotHasKey('result', $body);
    }

    public function test_subscribe_denies_banned_member(): void
    {
        $this->seedUser(10);
        $room = $this->makeRoom(10, null, 'group');
        $this->addMember($room, 10, ['status' => 'banned']);

        $request = $this->requestAs(0, [
            'user'    => '10',
            'channel' => "groups:room.{$room}",
            'client'  => 'abc-123',
        ]);

        $body = $this->decode($this->controller()->subscribe($request));

        $this->assertArrayHasKey('error', $body);
        $this->assertSame(403, $body['error']['code']);
    }

    public function test_subscribe_denies_left_member(): void
    {
        $this->seedUser(10);
        $room = $this->makeRoom(10, null, 'group');
        $this->addMember($room, 10, ['status' => 'left']);

        $request = $this->requestAs(0, [
            'user'    => '10',
            'channel' => "groups:room.{$room}",
            'client'  => 'abc-123',
        ]);

        $body = $this->decode($this->controller()->subscribe($request));

        $this->assertSame(403, $body['error']['code']);
    }

    public function test_subscribe_denies_unknown_channel_namespace(): void
    {
        $this->seedUser(10);
        $room = $this->makeRoom(10, null, 'group');
        $this->addMember($room, 10, ['role' => 'owner']);

        // A non-group channel must never be authorized by the proxy.
        $request = $this->requestAs(0, [
            'user'    => '10',
            'channel' => "chat:dm.10_11",
            'client'  => 'abc-123',
        ]);

        $body = $this->decode($this->controller()->subscribe($request));

        $this->assertSame(403, $body['error']['code']);
    }

    public function test_subscribe_denies_malformed_channel(): void
    {
        $this->seedUser(10);

        $request = $this->requestAs(0, [
            'user'    => '10',
            'channel' => 'groups:room.not-a-number',
            'client'  => 'abc-123',
        ]);

        $body = $this->decode($this->controller()->subscribe($request));

        $this->assertSame(403, $body['error']['code']);
    }
}
