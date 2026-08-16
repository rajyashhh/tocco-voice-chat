<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\VerifyLeaderCCMiddleWare;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Verifies that VerifyLeaderCCMiddleWare::findUserByToken consults the cache
 * (so the game client's repeated same-token batches don't re-query Sanctum on
 * every request) and that the miss-sentinel is mapped back to null.
 */
class VerifyLeaderCCTokenCacheTest extends TestCase
{
    private function cacheKeyFor(string $plainToken): string
    {
        return 'leadercc:token:' . hash('sha256', $plainToken);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** A cached resolution is returned without touching the DB/Sanctum. */
    public function test_cached_token_resolution_is_returned()
    {
        $plain = 'plainabc123';
        Cache::put($this->cacheKeyFor($plain), 4242, 60);

        $userId = (new VerifyLeaderCCMiddleWare())->findUserByToken($plain);

        $this->assertSame(4242, $userId);
    }

    /** The pipe-prefixed token form is split correctly before hashing/caching. */
    public function test_token_with_pipe_prefix_uses_plain_part()
    {
        $plain = 'plainwithpipe';
        // findUserByToken extracts the part AFTER the pipe as the plain token.
        Cache::put($this->cacheKeyFor($plain), 777, 60);

        $userId = (new VerifyLeaderCCMiddleWare())->findUserByToken('99|' . $plain);

        $this->assertSame(777, $userId);
    }

    /** The 0 sentinel (cached "no such token") is mapped back to null. */
    public function test_miss_sentinel_maps_to_null()
    {
        $plain = 'missingtoken';
        Cache::put($this->cacheKeyFor($plain), 0, 60);

        $userId = (new VerifyLeaderCCMiddleWare())->findUserByToken($plain);

        $this->assertNull($userId);
    }
}
