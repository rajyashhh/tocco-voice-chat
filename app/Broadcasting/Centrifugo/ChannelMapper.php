<?php

namespace App\Broadcasting\Centrifugo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Maps the legacy broadcast channel names to Centrifugo channel names.
 *
 * Phase 2 scope (REALTIME_CHAT_REBUILD_PLAN section 6.7) is 1:1 chat only:
 *   - user-{id}            -> user:#{id}      (per-participant personal channel)
 *   - conversation-user{id}-> user:#{id}      (per-participant personal channel)
 *   - conversation-{roomId}-> chat:dm.room.{roomId}  (shared 1:1 DM channel)
 *
 * Phase 9 scope (outside-the-room realtime migration, WAVE 1) adds the
 * broadcaster-backed banners + per-user counters. `without_namespace` on the node
 * blocks raw names, so every legacy name MUST land in a configured namespace:
 *
 *   GLOBAL (banner namespace — public-safe, no private data, server may publish):
 *     - gift_banner            -> banner:gift
 *     - super-lucky-box-chanel -> banner:lucky_box
 *     - end.room.boom          -> banner:boom
 *
 *   PER-USER (user namespace — the `#` boundary binds to the JWT `sub`, so user A
 *   can never read user B's counter; this is a security UPGRADE over the plain
 *   public legacy channels these replace):
 *     - unread-{id}            -> user:#{id}
 *     - status-user-{id}       -> user:#{id}
 *
 * Phase 9 WAVE 2 adds the 3 remaining outside-room banners. The earlier doc claim
 * of "no publisher" was WRONG: these DO have a Laravel broadcaster path —
 * `App\Events\BannerEvent` (ShouldBroadcast), dispatched from
 * `AllOpeningRoomsZegoRequest::handle()` via `event(new BannerEvent($data))`, whose
 * channel is `$data['messageContent']['event']` (one of the three legacy names).
 * Producers: WinLuckyGift::sendToStreamLuckyGift[V2] (win.lucky.gift.event),
 * the LeaderCCgame controller big-win banner (game.win.event), and
 * RoomComments::sendComments (room.comment.event). They are public-safe global
 * banners (no private data), so they map into the same `banner` namespace as WAVE 1:
 *
 *     - win.lucky.gift.event -> banner:lucky_gift
 *     - game.win.event       -> banner:games
 *     - room.comment.event   -> banner:comment
 *
 * Every remaining legacy channel (group chat, presence, notifications, in-room
 * realtime events) is still passed through unchanged. Passing them through keeps
 * `dual` mode safe: Centrifugo publishes them on their original names while the legacy
 * keeps delivering them as today, so nothing is silently dropped in transition.
 *
 * The class is deliberately the single seam for channel naming so that the later
 * switch to the canonical `chat:dm.{minUid}_{maxUid}` naming (once both
 * participant ids are threaded through the event layer) is a one-method change in
 * `dmChannelForRoom()`.
 */
class ChannelMapper
{
    /**
     * Legacy global-banner channel name -> banner namespace suffix.
     *
     * WAVE 1 + WAVE 2. The legacy name is BOTH the legacy broadcast channel and the
     * `broadcastAs` event name, but mapping keys off the channel name only; the
     * event name is preserved untouched in the broadcaster envelope.
     */
    private const BANNER_MAP = [
        // WAVE 1
        'gift_banner'            => 'gift',
        'super-lucky-box-chanel' => 'lucky_box',
        'end.room.boom'          => 'boom',
        // WAVE 2 (BannerEvent publisher; see class doc)
        'win.lucky.gift.event'   => 'lucky_gift',
        'game.win.event'         => 'games',
        'room.comment.event'     => 'comment',
    ];

    /**
     * Translate a single legacy channel name into one or more Centrifugo channels.
     *
     * Returns an array because a legacy channel may fan out to several Centrifugo
     * channels (e.g. a future group channel mapping to many `user:#{id}`).
     *
     * @param  string  $legacyChannel
     * @return string[]
     */
    public function map(string $legacyChannel): array
    {
        $legacyChannel = trim($legacyChannel);

        if ($legacyChannel === '') {
            return [];
        }

        // conversation-user{id}  -> user:#{id}
        // (check before the broader conversation-{id} rule)
        if (preg_match('/^conversation-user(\d+)$/', $legacyChannel, $m)) {
            return [$this->userChannel($m[1])];
        }

        // conversation-pair-{a}_{b} -> shared 1:1 DM channel, derived WITHOUT a DB
        // lookup. The event already knows both participant ids, so it emits the
        // pair form directly; we build the canonical order-independent name the
        // exact same way CentrifugoAuthController::dmChannel() signs it and the
        // client's _chatChannelName() subscribes to it. This is the resilient path
        // for live messages — it can never fall back to the dead chat:dm.room.{id}.
        // (Checked before the broader conversation-{id} rule so the pair form wins.)
        if (preg_match('/^conversation-pair-(\d+)_(\d+)$/', $legacyChannel, $m)) {
            return [$this->dmChannelForPair($m[1], $m[2])];
        }

        // conversation-{roomId} -> shared 1:1 DM channel
        if (preg_match('/^conversation-(\d+)$/', $legacyChannel, $m)) {
            return [$this->dmChannelForRoom($m[1])];
        }

        // user-{id} -> user:#{id}
        if (preg_match('/^user-(\d+)$/', $legacyChannel, $m)) {
            return [$this->userChannel($m[1])];
        }

        // ── Phase 9 WAVE 1: outside-room banners + per-user counters ──────────

        // unread-{id} -> user:#{id}  (per-user, user-limited; counter is private)
        if (preg_match('/^unread-(\d+)$/', $legacyChannel, $m)) {
            return [$this->userChannel($m[1])];
        }

        // status-user-{id} -> user:#{id}  (per-user game status; private)
        if (preg_match('/^status-user-(\d+)$/', $legacyChannel, $m)) {
            return [$this->userChannel($m[1])];
        }

        // room.boom.rewards.{id} (presence) -> banner:boom_rewards. In-room boom
        // winner reveal, migrated off the legacy presence channel. The payload
        // carries `winners` keyed by user id and the client self-filters, so a
        // shared banner channel is safe (no per-room subscription needed).
        if (preg_match('/^(?:presence-)?room\.boom\.rewards\.\d+$/', $legacyChannel)) {
            return [$this->bannerChannel('boom_rewards')];
        }

        // Global banners (WAVE 1 + WAVE 2) -> banner:* (public-safe, server-published only).
        if (isset(self::BANNER_MAP[$legacyChannel])) {
            return [$this->bannerChannel(self::BANNER_MAP[$legacyChannel])];
        }

        // Out of scope (group/presence/in-room) — pass through unchanged so dual
        // mode does not drop the event.
        return [$legacyChannel];
    }

    /**
     * Translate a list of legacy channels, de-duplicating the result.
     *
     * @param  string[]  $legacyChannels
     * @return string[]
     */
    public function mapMany(array $legacyChannels): array
    {
        $mapped = [];

        foreach ($legacyChannels as $legacyChannel) {
            foreach ($this->map((string) $legacyChannel) as $channel) {
                $mapped[$channel] = true;
            }
        }

        return array_keys($mapped);
    }

    /**
     * Per-user personal channel (user-limited via the `#` boundary).
     */
    public function userChannel(string|int $userId): string
    {
        return 'user:#' . $userId;
    }

    /**
     * Global banner channel (public namespace; only the server may publish).
     * Prefix is config-driven to stay aligned with the auth surface, with a
     * literal fallback matching the live node config.
     */
    public function bannerChannel(string $suffix): string
    {
        return config('centrifugo.channels.banner_prefix', 'banner:') . $suffix;
    }

    /**
     * The fixed set of global banner channels (WAVE 1 + WAVE 2).
     *
     * Single source of truth shared with the connection-token `channels` claim so
     * the auto-subscribed banner set can never drift from what the broadcaster
     * actually publishes to.
     *
     * @return string[]
     */
    public function bannerChannels(): array
    {
        $channels = [];
        foreach (self::BANNER_MAP as $suffix) {
            $channels[] = $this->bannerChannel($suffix);
        }
        foreach (self::DYNAMIC_BANNER_SUFFIXES as $suffix) {
            $channels[] = $this->bannerChannel($suffix);
        }

        return $channels;
    }

    /**
     * Banner suffixes mapped via regex from dynamic legacy names (e.g.
     * `room.boom.rewards.{id}`) rather than the static {@see BANNER_MAP}. They
     * must still be in the auto-subscribed banner set so clients receive them.
     */
    private const DYNAMIC_BANNER_SUFFIXES = ['boom_rewards'];

    /**
     * Shared 1:1 DM channel — MUST match exactly what the client subscribes to
     * and what CentrifugoAuthController::subscription() signs into the token,
     * which is `chat:dm.{minUid}_{maxUid}` (order-independent on the user pair).
     *
     * The legacy `conversation-{roomId}` name only carries the room id, so we
     * resolve the room's two participants (user_id / user_id2) and build the
     * canonical pair name. Cached briefly because the same room broadcasts many
     * messages in a row, and the room→pair mapping never changes.
     *
     * If the pair can't be resolved (corrupt/legacy room) we still fall back to the
     * old room-based name rather than throwing — never onto the wrong user's
     * channel — but we log it at CRITICAL because `chat:dm.room.{id}` is a DEAD
     * channel (no client ever subscribes to it): every DM published through this
     * branch is silently lost, so it must be visible in observability instead of
     * being swallowed.
     */
    public function dmChannelForRoom(string|int $roomId): string
    {
        $pair = Cache::remember(
            'dm_pair_' . $roomId,
            now()->addMinutes(30),
            function () use ($roomId) {
                $room = DB::table('chat_rooms')
                    ->select(['user_id', 'user_id2'])
                    ->find($roomId);
                if ($room === null) return null;
                $a = (int) $room->user_id;
                $b = (int) $room->user_id2;
                if ($a <= 0 || $b <= 0) return null;
                return [min($a, $b), max($a, $b)];
            }
        );

        if ($pair === null) {
            Log::critical('ChannelMapper.dm_pair_unresolved', [
                'chat_room_id' => $roomId,
                'fallback_channel' => $this->dmPrefix() . 'room.' . $roomId,
                'impact' => 'DM published to a dead channel; client receives nothing',
            ]);

            return $this->dmPrefix() . 'room.' . $roomId;
        }

        return $this->dmChannelForPair($pair[0], $pair[1]);
    }

    /**
     * Canonical 1:1 DM channel name for a participant pair — order-independent.
     *
     * The single builder for the `chat:dm.{min}_{max}` name. It MUST stay byte-for-
     * byte identical to CentrifugoAuthController::dmChannel() (which signs the
     * subscription token's `channel` claim) and to the client's _chatChannelName()
     * (which builds the subscribe name): Centrifugo rejects a token whose channel
     * claim differs from the joined channel, and a publish to any other string is
     * silently lost. Derives the prefix from the same config key the auth surface
     * uses so the three sites can never drift.
     */
    public function dmChannelForPair(string|int $a, string|int $b): string
    {
        $a = (int) $a;
        $b = (int) $b;

        return $this->dmPrefix() . min($a, $b) . '_' . max($a, $b);
    }

    /**
     * 1:1 DM channel prefix (config-driven, literal fallback matching the node).
     */
    private function dmPrefix(): string
    {
        return config('centrifugo.channels.dm_prefix', 'chat:dm.');
    }
}
