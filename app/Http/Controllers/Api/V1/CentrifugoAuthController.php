<?php

namespace App\Http\Controllers\Api\V1;

use App\Broadcasting\Centrifugo\ChannelMapper;
use App\Http\Controllers\Controller;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Http\Repositories\BlacklistRepository;

/**
 * Centrifugo authentication surface (REALTIME_CHAT_REBUILD_PLAN section 4.2).
 *
 * Three endpoints, each mapping to one of the three Centrifugo auth mechanisms
 * the plan fixes — kept strictly separated because the node cannot mix them on a
 * single channel (plan 4.2 critical constraint):
 *
 *   POST /centrifugo/token        (a) connection JWT (HS256) for the `user:#{id}`
 *                                     personal channel + the connection itself.
 *   POST /centrifugo/subscription (b) subscription token for the 1:1 DM channel
 *                                     `chat:dm.{min}_{max}` — the channel is
 *                                     derived server-side from (caller, peer),
 *                                     so the caller is a party by construction.
 *   POST /centrifugo/subscribe    (c) subscribe proxy for `groups:room.{id}` —
 *                                     Centrifugo calls this server-to-server to
 *                                     authorize live group membership.
 *
 * (a)/(b) sit behind the app's existing sanctum auth (the route group). (c) is a
 * node-to-Laravel call guarded by the shared proxy secret (VerifyCentrifugoProxy),
 * NOT sanctum — there is no user session on that hop.
 */
class CentrifugoAuthController extends Controller
{
    private const ALG = 'HS256';

    /**
     * (a) Connection JWT.
     *
     * centrifuge-dart calls this in its getToken callback and again before exp to
     * refresh. Claims: sub=(string)user->id, exp=now+ttl, info={name,avatar}.
     *
     * The `channels` claim auto-subscribes the connection to the global banner
     * channels at connect (phase 9 WAVE 1 + WAVE 2) — they are public-safe (no
     * private data, `allow_subscribe_for_client:true`, no token), so the client
     * receives the outside-room banners on the same socket with zero per-channel
     * tokens. The set is derived from ChannelMapper::bannerChannels() (single
     * source of truth shared with the broadcaster), so it always matches exactly
     * what the publisher writes to.
     * Per-user channels (`user:#{id}`) are NOT listed here: the client creates
     * that subscription itself and the user namespace is authorized by `sub`.
     * A banner channel added/renamed later reaches existing clients only after a
     * token refresh (<= token_ttl) — acceptable for the fixed banner set.
     */
    public function token(Request $request): JsonResponse
    {
        $secret = $this->hmacSecret();
        if ($secret === null) {
            return $this->misconfigured();
        }

        $user = $request->user();

        $now = time();
        $payload = [
            'sub' => (string) $user->id,
            'iat' => $now,
            'exp' => $now + (int) config('centrifugo.token_ttl', 3600),
            'info' => [
                'name'   => (string) ($user->name ?? ''),
                'avatar' => (string) ($user->avatar ?? ''),
            ],
            'channels' => (new ChannelMapper())->bannerChannels(),
        ];

        $token = JWT::encode($payload, $secret, self::ALG);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'expires_at' => $payload['exp'],
        ]);
    }

    /**
     * (b) Subscription token for a 1:1 DM channel.
     *
     * The client passes the other participant id (`user_id`). We derive the
     * canonical, order-independent channel `chat:dm.{min}_{max}` and sign a token
     * whose `channel` claim matches the requested channel EXACTLY — Centrifugo
     * rejects a token whose channel claim differs from the channel being joined.
     *
     * The token is issued whether or not a ChatRoom exists yet: the channel is
     * derived server-side from (caller, peer), so the caller is a party to it by
     * construction, and subscribing BEFORE the first message is required for that
     * message to arrive in realtime. The old "room must already exist" gate
     * rejected every brand-new conversation (chat opened from a profile/room
     * before the first message) — 2,248x403 in 24h with the SDK retry-looping on
     * each one (2026-06-12).
     *
     * Refuses (403) only for a self-DM or a blocked pair; unknown peer is 422.
     */
    public function subscription(Request $request): JsonResponse
    {
        $secret = $this->hmacSecret();
        if ($secret === null) {
            return $this->misconfigured();
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $caller = $request->user();
        $other  = (int) $validator->validated()['user_id'];

        if ($other === (int) $caller->id) {
            return $this->forbidden('Cannot open a DM with yourself');
        }

        if ((new BlacklistRepository())->isUserBlocked((int) $caller->id, $other)) {
            return $this->forbidden('Conversation is not available');
        }

        $channel = $this->dmChannel((int) $caller->id, $other);

        $now = time();
        $payload = [
            'sub'     => (string) $caller->id,
            'channel' => $channel,
            'iat'     => $now,
            'exp'     => $now + (int) config('centrifugo.subscription_ttl', 3600),
        ];

        $token = JWT::encode($payload, $secret, self::ALG);

        return response()->json([
            'success' => true,
            'channel' => $channel,
            'token'   => $token,
            'expires_at' => $payload['exp'],
        ]);
    }

    /**
     * (c) Subscribe proxy for group channels.
     *
     * Called by the Centrifugo node (guarded by VerifyCentrifugoProxy), NOT a
     * client. Body shape per the node's subscribe-proxy contract:
     *   { "user": "<id-string>", "channel": "groups:room.{id}", "client": "..." }
     *
     * Grants the subscription iff the user is an ACTIVE member of the group's
     * unified room. Anything else -> the Centrifugo permission-denied envelope.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $userId  = (int) $request->input('user');
        $channel = (string) $request->input('channel', '');

        $roomId = $this->groupRoomId($channel);

        if ($userId <= 0 || $roomId === null) {
            return $this->proxyDenied();
        }

        // Membership decision cached for 60s, keyed on (roomId, userId). This is
        // the highest-frequency DB touch at scale (the node calls this on every
        // (re)subscribe, server-to-server). Caveat: on leave/ban the gate stays
        // open for up to 60s. Member-status mutations are scattered across the
        // chat services (leave/kick/ban/deleteGroup) with no single chokepoint,
        // so we accept the 60s window for a subscribe gate rather than couple
        // every mutation site to this cache key; the next refresh re-checks.
        $isActiveMember = \Illuminate\Support\Facades\Cache::remember(
            "centgrp:{$roomId}:{$userId}",
            60,
            fn () => ChatRoomMember::query()
                ->where('chat_room_id', $roomId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists()
        );

        if (! $isActiveMember) {
            return $this->proxyDenied();
        }

        // Empty result object = "allowed" in the Centrifugo proxy contract.
        return response()->json([
            'result' => (object) [],
        ]);
    }

    // ---------------------------------------------------------------------
    // helpers
    // ---------------------------------------------------------------------

    /**
     * Canonical 1:1 DM channel name: order-independent on the participant pair.
     * Must stay identical to the broadcaster's ChannelMapper target so a token
     * issued here matches the channel the publisher writes to.
     */
    private function dmChannel(int $a, int $b): string
    {
        $min = min($a, $b);
        $max = max($a, $b);

        return config('centrifugo.channels.dm_prefix', 'chat:dm.') . "{$min}_{$max}";
    }

    /**
     * Extract the unified chat_room_id from a `groups:room.{id}` channel name.
     * Returns null when the name does not match the group namespace pattern.
     */
    private function groupRoomId(string $channel): ?int
    {
        $prefix = config('centrifugo.channels.group_prefix', 'groups:room.');

        if (! str_starts_with($channel, $prefix)) {
            return null;
        }

        $id = substr($channel, strlen($prefix));

        return ctype_digit($id) && (int) $id > 0 ? (int) $id : null;
    }

    private function hmacSecret(): ?string
    {
        $secret = (string) config('centrifugo.hmac_secret', '');

        return $secret === '' ? null : $secret;
    }

    private function misconfigured(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Realtime transport is not configured',
        ], 503);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Centrifugo subscribe-proxy permission-denied envelope.
     */
    private function proxyDenied(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code'    => 403,
                'message' => 'permission denied',
            ],
        ]);
    }
}
