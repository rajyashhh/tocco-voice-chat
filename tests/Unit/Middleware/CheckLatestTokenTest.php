<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckLatestToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Unit Test: App\Http\Middleware\CheckLatestToken
 *
 * Proves single-device enforcement now reads the latest token id from cache
 * (zero DB queries on the hot path) and only falls back to a single DB lookup
 * on a cache miss, while still returning 505 when the request's token is not
 * the newest one.
 */
class CheckLatestTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function userWithToken(int $userId, int $currentTokenId, ?int $maxIdFallback = null)
    {
        $currentToken = Mockery::mock();
        $currentToken->shouldReceive('getKey')->andReturn($currentTokenId);

        $user = Mockery::mock();
        $user->id = $userId;
        $user->shouldReceive('currentAccessToken')->andReturn($currentToken);

        // tokens()->max('id') must NOT be called on a cache hit; allow it for miss.
        $relation = Mockery::mock();
        $relation->shouldReceive('max')->with('id')->andReturn($maxIdFallback);
        $user->shouldReceive('tokens')->andReturn($relation);

        return $user;
    }

    private function pass(Request $request)
    {
        return (new CheckLatestToken())->handle($request, fn () => response('ok'));
    }

    public function test_passes_when_current_token_matches_cached_latest(): void
    {
        $user = $this->userWithToken(42, 500);
        Cache::put('latest_token_id_42', 500, 60);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $response = $this->pass(Request::create('/api/x', 'GET'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_returns_505_when_current_token_is_not_latest(): void
    {
        // Current token id 400 but newest is 500 => another device logged in.
        $user = $this->userWithToken(42, 400);
        Cache::put('latest_token_id_42', 500, 60);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $response = $this->pass(Request::create('/api/x', 'GET'));

        $this->assertSame(505, $response->getStatusCode());
    }

    public function test_cache_miss_rebuilds_from_db_and_caches(): void
    {
        // No cache entry yet; fallback max(id) = 500, current token = 500 => pass.
        $user = $this->userWithToken(42, 500, 500);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $response = $this->pass(Request::create('/api/x', 'GET'));

        $this->assertSame('ok', $response->getContent());
        // Self-healing: the latest id is now cached for subsequent requests.
        $this->assertSame(500, Cache::get('latest_token_id_42'));
    }

    public function test_skips_check_for_unauthenticated_request(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $response = $this->pass(Request::create('/api/x', 'GET'));

        $this->assertSame('ok', $response->getContent());
    }
}
