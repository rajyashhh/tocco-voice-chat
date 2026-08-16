<?php

namespace Tests\Unit\Services;

use App\Services\BanGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit Test: App\Services\BanGuard
 *
 * Verifies the cached ban snapshot drives both login- and action-ban decisions,
 * that the common (not-banned) case is served from cache with no DB access, and
 * that invalidate() clears the snapshot. The cache is pre-seeded so these tests
 * never touch the DB.
 */
class BanGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function request(string $ip = '203.0.113.5', ?string $device = 'dev-1'): Request
    {
        $request = Request::create('/api/rooms/enter_room', 'POST');
        $request->server->set('REMOTE_ADDR', $ip);
        if ($device !== null) {
            $request->headers->set('x-device-token', $device);
        }

        return $request;
    }

    public function test_not_banned_returns_null_from_empty_cached_snapshot(): void
    {
        // Empty snapshot = the overwhelmingly common case; no DB hit.
        Cache::put('user_ban_state_uuid-1', [], 12);

        $guard = new BanGuard();

        $this->assertNull($guard->loginBanMessage('uuid-1', $this->request()));
        $this->assertNull($guard->actionBanMessage('uuid-1', $this->request()));
    }

    public function test_null_uuid_short_circuits(): void
    {
        $guard = new BanGuard();

        $this->assertNull($guard->loginBanMessage(null, $this->request()));
        $this->assertNull($guard->actionBanMessage(null, $this->request()));
    }

    public function test_login_ban_message_built_from_cached_snapshot(): void
    {
        Cache::put('user_ban_state_uuid-1', [[
            'uid'            => 'uuid-1',
            'type'           => 'normal',
            'ip'             => null,
            'device_number'  => null,
            'duration'       => 24,
            'description_ar' => 'سبب',
            'description_en' => 'reason',
            'ban_type_id'    => null,
        ]], 12);

        $message = (new BanGuard())->loginBanMessage('uuid-1', $this->request());

        $this->assertNotNull($message);
    }

    public function test_login_ban_does_not_match_unrelated_ip_or_device(): void
    {
        // Ban scoped to a different ip/device must not ban the current request.
        Cache::put('user_ban_state_uuid-2', [[
            'uid'            => 'other-uuid',
            'type'           => 'ip',
            'ip'             => '198.51.100.1',
            'device_number'  => null,
            'duration'       => 24,
            'description_ar' => 'سبب',
            'description_en' => 'reason',
            'ban_type_id'    => null,
        ]], 12);

        $this->assertNull(
            (new BanGuard())->loginBanMessage('uuid-2', $this->request('203.0.113.5', 'dev-1'))
        );
    }

    public function test_invalidate_forgets_snapshot(): void
    {
        Cache::put('user_ban_state_uuid-1', [['type' => 'normal']], 12);

        BanGuard::invalidate('uuid-1');

        $this->assertNull(Cache::get('user_ban_state_uuid-1'));
    }

    public function test_invalidate_with_null_uuid_is_noop(): void
    {
        Cache::put('user_ban_state_uuid-1', [['type' => 'normal']], 12);

        BanGuard::invalidate(null);

        // Unrelated key untouched.
        $this->assertNotNull(Cache::get('user_ban_state_uuid-1'));
    }
}
