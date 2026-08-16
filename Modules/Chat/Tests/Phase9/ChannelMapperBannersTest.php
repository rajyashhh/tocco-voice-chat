<?php

namespace Modules\Chat\Tests\Phase9;

use App\Broadcasting\Centrifugo\ChannelMapper;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Phase 9 WAVE 1 — outside-room banners + per-user counters channel mapping.
 *
 * Pure mapping assertions: every Pusher channel migrated in phase 9 must resolve
 * to the correct Centrifugo channel/namespace, the two per-user counters must
 * stay user-limited (the `#` boundary), the three WAVE-2 names (no backend
 * publisher yet) must STILL pass through unchanged, and the existing chat
 * mappings must be untouched. This is the regression guard against both the
 * silent-drop and the cross-user-leak risks of the migration.
 *
 * Deliberately a bare PHPUnit test (no Laravel boot): the migrated WAVE-1 / WAVE-2
 * branches and the chat user branches are pure string logic with no DB. The only
 * framework touchpoint is the `config()` lookup inside bannerChannel(), so we
 * stand up a minimal container with just a config repository — the test stays
 * deterministic and does not require the MySQL the full TestCase boot needs.
 *
 * (The DB-backed conversation-{roomId} -> chat:dm.{min}_{max} branch is covered
 * by the Phase 3 auth/send-path suites that already have a database; this unit
 * test asserts only the no-DB branches.)
 */
class ChannelMapperBannersTest extends TestCase
{
    private const BANNER_PREFIX = 'banner:';

    private const DM_PREFIX = 'chat:dm.';

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal container so the `config()` helper resolves the banner prefix
        // exactly as production config/centrifugo.php defines it, with no app boot.
        $container = Container::setInstance(new Container());
        $container->instance('config', new ConfigRepository([
            'centrifugo' => [
                'channels' => [
                    'banner_prefix' => self::BANNER_PREFIX,
                    'dm_prefix'     => self::DM_PREFIX,
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    private function mapper(): ChannelMapper
    {
        return new ChannelMapper();
    }

    // ---------------------------------------------------------------------
    // GLOBAL banners -> banner:* (public namespace)
    // ---------------------------------------------------------------------

    /** @dataProvider bannerCases */
    public function test_global_banner_maps_to_banner_namespace(string $legacy, string $expected): void
    {
        $this->assertSame([$expected], $this->mapper()->map($legacy));
    }

    public static function bannerCases(): array
    {
        return [
            // WAVE 1
            'gift'      => ['gift_banner', 'banner:gift'],
            'lucky_box' => ['super-lucky-box-chanel', 'banner:lucky_box'],
            'boom'      => ['end.room.boom', 'banner:boom'],
            // WAVE 2 (BannerEvent publisher)
            'lucky_gift'=> ['win.lucky.gift.event', 'banner:lucky_gift'],
            'games'     => ['game.win.event', 'banner:games'],
            'comment'   => ['room.comment.event', 'banner:comment'],
        ];
    }

    public function test_every_banner_lands_in_the_banner_namespace_prefix(): void
    {
        foreach ([
            'gift_banner', 'super-lucky-box-chanel', 'end.room.boom',
            'win.lucky.gift.event', 'game.win.event', 'room.comment.event',
        ] as $legacy) {
            $mapped = $this->mapper()->map($legacy);
            $this->assertCount(1, $mapped);
            $this->assertStringStartsWith(self::BANNER_PREFIX, $mapped[0]);
        }
    }

    public function test_banner_prefix_is_config_driven(): void
    {
        // The auth surface (connection-token `channels` claim) derives the banner
        // names from the same config key; if the node namespace is renamed, the
        // mapper must follow it rather than hard-coding 'banner:'.
        config()->set('centrifugo.channels.banner_prefix', 'bn:');

        $this->assertSame(['bn:gift'], $this->mapper()->map('gift_banner'));
        $this->assertSame(
            [
                'bn:gift', 'bn:lucky_box', 'bn:boom',
                'bn:lucky_gift', 'bn:games', 'bn:comment',
                'bn:boom_rewards',
            ],
            $this->mapper()->bannerChannels()
        );
    }

    public function test_banner_channels_set_is_the_wave1_and_wave2_banners(): void
    {
        // Single source of truth shared with the connection-token `channels` claim.
        // Includes dynamic-suffix channels (e.g. boom_rewards).
        $this->assertSame(
            [
                'banner:gift', 'banner:lucky_box', 'banner:boom',
                'banner:lucky_gift', 'banner:games', 'banner:comment',
                'banner:boom_rewards',
            ],
            $this->mapper()->bannerChannels()
        );
    }

    public function test_boom_rewards_dynamic_channel_maps_to_banner_namespace(): void
    {
        // room.boom.rewards.{id} is a dynamic-suffix banner (per-room presence).
        $this->assertSame(['banner:boom_rewards'], $this->mapper()->map('room.boom.rewards.123'));
        $this->assertSame(['banner:boom_rewards'], $this->mapper()->map('room.boom.rewards.9999'));
        $this->assertStringStartsWith(self::BANNER_PREFIX, $this->mapper()->map('room.boom.rewards.1')[0]);
    }

    // ---------------------------------------------------------------------
    // PER-USER counters -> user:#{id} (user-limited; NEVER public)
    // ---------------------------------------------------------------------

    public function test_unread_counter_maps_to_user_limited_channel(): void
    {
        $this->assertSame(['user:#7'], $this->mapper()->map('unread-7'));
    }

    public function test_game_status_maps_to_user_limited_channel(): void
    {
        $this->assertSame(['user:#7'], $this->mapper()->map('status-user-7'));
    }

    /** @dataProvider perUserCases */
    public function test_per_user_channel_is_user_limited_for_the_exact_id(string $legacy, string $id): void
    {
        // SECURITY: the `#` boundary in `user:#{id}` binds the channel to the JWT
        // `sub`, so user A can never subscribe to user B's counter. Assert the
        // mapped name carries the `#` boundary and the EXACT requested id.
        $mapped = $this->mapper()->map($legacy);

        $this->assertSame(['user:#' . $id], $mapped);
        $this->assertStringContainsString('#', $mapped[0]);
        $this->assertSame('user:#' . $id, $mapped[0]);
    }

    public static function perUserCases(): array
    {
        return [
            'unread'      => ['unread-42', '42'],
            'game_status' => ['status-user-42', '42'],
            'unread_other'=> ['unread-1009', '1009'],
        ];
    }

    public function test_per_user_channels_never_map_into_public_banner_namespace(): void
    {
        // A per-user counter on a public banner:* channel would let any client
        // read any user's counter. Assert they resolve to the user namespace only.
        foreach (['unread-42', 'status-user-42'] as $legacy) {
            $mapped = $this->mapper()->map($legacy);
            $this->assertSame(['user:#42'], $mapped);
            $this->assertStringStartsNotWith(self::BANNER_PREFIX, $mapped[0]);
        }
    }

    public function test_per_user_channels_for_distinct_users_never_collide(): void
    {
        // Two different users' counters must resolve to two different, user-bound
        // channels — never a shared/public one.
        $a = $this->mapper()->map('unread-100');
        $b = $this->mapper()->map('unread-200');

        $this->assertSame(['user:#100'], $a);
        $this->assertSame(['user:#200'], $b);
        $this->assertNotSame($a, $b);
    }

    // ---------------------------------------------------------------------
    // WAVE 2 — now mapped to the public banner namespace (BannerEvent publisher)
    // ---------------------------------------------------------------------

    /** @dataProvider wave2Cases */
    public function test_wave2_channels_map_to_banner_namespace(string $legacy, string $expected): void
    {
        // These DO have a Laravel publisher (App\Events\BannerEvent, dispatched from
        // AllOpeningRoomsZegoRequest), so they migrate into the public banner
        // namespace alongside WAVE 1.
        $this->assertSame([$expected], $this->mapper()->map($legacy));
    }

    public static function wave2Cases(): array
    {
        return [
            'lucky_gift' => ['win.lucky.gift.event', 'banner:lucky_gift'],
            'games'      => ['game.win.event', 'banner:games'],
            'comment'    => ['room.comment.event', 'banner:comment'],
        ];
    }

    // ---------------------------------------------------------------------
    // existing chat mapping is untouched by the WAVE-1 additions
    // ---------------------------------------------------------------------

    public function test_existing_user_channel_mapping_is_unchanged(): void
    {
        $this->assertSame(['user:#9'], $this->mapper()->map('user-9'));
        $this->assertSame(['user:#9'], $this->mapper()->map('conversation-user9'));
    }

    public function test_conversation_user_is_matched_before_conversation_room(): void
    {
        // `conversation-user{id}` must hit the personal-channel rule, not the
        // numeric `conversation-{roomId}` DM rule (which would try a DB lookup).
        $this->assertSame(['user:#15'], $this->mapper()->map('conversation-user15'));
    }

    public function test_conversation_pair_maps_to_canonical_dm_channel_without_db(): void
    {
        // The pair form carries both participant ids, so it resolves to the exact
        // chat:dm.{min}_{max} the client subscribes to and the auth surface signs —
        // with no DB lookup and no dead-channel fallback. Order-independent.
        $this->assertSame(['chat:dm.1_2'], $this->mapper()->map('conversation-pair-1_2'));
        $this->assertSame(['chat:dm.1_2'], $this->mapper()->map('conversation-pair-2_1'));
        $this->assertSame(['chat:dm.42_77'], $this->mapper()->map('conversation-pair-77_42'));
    }

    public function test_conversation_pair_is_matched_before_conversation_room(): void
    {
        // `conversation-pair-{a}_{b}` must hit the no-DB pair rule, never the
        // numeric `conversation-{roomId}` DM rule that does a DB lookup.
        $this->assertSame(['chat:dm.3_8'], $this->mapper()->map('conversation-pair-8_3'));
    }

    public function test_empty_or_whitespace_channel_maps_to_nothing(): void
    {
        $this->assertSame([], $this->mapper()->map(''));
        $this->assertSame([], $this->mapper()->map('   '));
    }

    public function test_unknown_channel_passes_through_unchanged(): void
    {
        // Anything not in scope (group/presence/notifications/in-room) is delivered
        // on its original name so dual mode never drops it.
        $this->assertSame(['some-other-channel'], $this->mapper()->map('some-other-channel'));
        $this->assertSame(['notification-channel-5'], $this->mapper()->map('notification-channel-5'));
    }

    // ---------------------------------------------------------------------
    // mapMany — fan-in/dedupe across the migrated channels
    // ---------------------------------------------------------------------

    public function test_mapmany_dedupes_banner_and_user_targets(): void
    {
        // gift_banner + unread-5 + status-user-5 -> banner:gift + user:#5 (one each).
        $this->assertSame(
            ['banner:gift', 'user:#5'],
            $this->mapper()->mapMany(['gift_banner', 'unread-5', 'status-user-5'])
        );
    }

    public function test_mapmany_covers_all_banners_once_each(): void
    {
        $this->assertSame(
            ['banner:gift', 'banner:lucky_box', 'banner:boom'],
            $this->mapper()->mapMany([
                'gift_banner', 'super-lucky-box-chanel', 'end.room.boom',
            ])
        );
    }
}
