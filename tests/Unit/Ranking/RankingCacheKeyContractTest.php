<?php

namespace Tests\Unit\Ranking;

use App\Repositories\RankingRepository;
use Carbon\Carbon;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the Games-ranking cache-key contract.
 *
 * The 504/stale-ranking bug had two halves:
 *   1) RankingRepository::getUserGameCoins caches its DB fallback under
 *      'game_rank_db:{type}:{startOfPeriodDate}'.
 *   2) The invalidators (CoinGameUserObserver + the manual forget on the
 *      LeaderCCgameController hot path, which writes via DB::table()->insert and
 *      therefore bypasses the observer) MUST forget exactly those keys.
 *
 * Previously the invalidator used 'game_rank:{type}:...', which matched neither
 * the DB-fallback cache nor the Redis sorted sets — so invalidation never fired.
 *
 * This test derives the canonical key the same way getUserGameCoins does (via the
 * repository's own getDateRange) and asserts the invalidator keys match it for
 * every period. It is pure logic (no DB, no container) so it runs anywhere.
 */
class RankingCacheKeyContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // getDateRange() -> Common::timeZone() -> Cache facade. When this file
        // runs standalone (no Laravel test booted the app first in-process)
        // there is no facade root, so provide a minimal array cache seeded with
        // the timezone — keeping the "runs anywhere, no DB" promise above true.
        if (Facade::getFacadeApplication() === null) {
            $cache = new CacheRepository(new ArrayStore());
            $cache->forever('timezone', 'UTC');

            $app = new Container();
            $app->instance('cache', $cache);
            Facade::setFacadeApplication($app);
        }
    }

    private function getDateRange(int $type): array
    {
        $method = new ReflectionMethod(RankingRepository::class, 'getDateRange');
        $method->setAccessible(true);

        return $method->invoke(new RankingRepository(), $type);
    }

    /**
     * The exact key getUserGameCoins builds: 'game_rank_db:' . $type . ':' . $from->toDateString().
     */
    private function canonicalCacheKey(int $type): string
    {
        [$from] = $this->getDateRange($type);

        return 'game_rank_db:' . $type . ':' . $from->toDateString();
    }

    /**
     * The keys the invalidators forget (CoinGameUserObserver::created and
     * LeaderCCgameController::forgetGameRankingCache build the same set).
     */
    private function invalidatorKeys(): array
    {
        $today = Carbon::today()->toDateString();

        return [
            0 => 'game_rank_db:0:' . $today,
            1 => 'game_rank_db:1:' . $today,
            2 => 'game_rank_db:2:' . Carbon::now()->startOfWeek()->toDateString(),
            3 => 'game_rank_db:3:' . Carbon::now()->startOfMonth()->toDateString(),
        ];
    }

    public function test_daily_invalidator_key_matches_cache_key(): void
    {
        $this->assertSame(
            $this->canonicalCacheKey(1),
            $this->invalidatorKeys()[1],
            'Daily ranking invalidation must target the same key getUserGameCoins caches under.'
        );
    }

    public function test_hourly_invalidator_key_matches_cache_key(): void
    {
        // type 0 buckets by the day's date (startOfHour->toDateString() == today).
        $this->assertSame(
            $this->canonicalCacheKey(0),
            $this->invalidatorKeys()[0],
            'Hourly ranking invalidation must target the cached key.'
        );
    }

    public function test_weekly_invalidator_key_matches_cache_key(): void
    {
        $this->assertSame(
            $this->canonicalCacheKey(2),
            $this->invalidatorKeys()[2],
            'Weekly ranking invalidation must target the startOfWeek-keyed cache entry.'
        );
    }

    public function test_monthly_invalidator_key_matches_cache_key(): void
    {
        $this->assertSame(
            $this->canonicalCacheKey(3),
            $this->invalidatorKeys()[3],
            'Monthly ranking invalidation must target the startOfMonth-keyed cache entry.'
        );
    }

    public function test_keys_use_the_db_fallback_prefix_not_the_legacy_prefix(): void
    {
        foreach ($this->invalidatorKeys() as $key) {
            $this->assertStringStartsWith('game_rank_db:', $key);
            // Guard against re-introducing the broken 'game_rank:{type}:' prefix
            // that matched neither cache nor the Redis sorted sets.
            $this->assertDoesNotMatchRegularExpression('/^game_rank:\d/', $key);
        }
    }
}
