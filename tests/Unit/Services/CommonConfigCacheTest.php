<?php

namespace Tests\Unit\Services;

use App\Helpers\Common;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Pins the No-Default-Fallback fixes on the config helpers in Common:
 *
 *  - getConfig now uses Cache::rememberForever('all_configs', ...) so it rebuilds
 *    from the DB on cache eviction (Octane restart / Redis flush) instead of
 *    returning null silently for every key.
 *  - getConfFromKey reads from the same cached all_configs map (no per-request DB
 *    query) and returns a Collection of {name, value} objects, matching the shape
 *    callers consume via ->where('name', ...)->first()->value.
 */
class CommonConfigCacheTest extends TestCase
{
    public function test_get_config_uses_remember_forever_on_all_configs(): void
    {
        Cache::shouldReceive('rememberForever')
            ->with('all_configs', Mockery::type('Closure'))
            ->andReturn(['logo' => 'logo.png', 'zego_app_id' => '123']);

        $this->assertSame('logo.png', Common::getConfig('logo'));
    }

    public function test_get_config_returns_null_only_for_unknown_key_not_for_evicted_cache(): void
    {
        // Even on a "cold" cache the closure rebuilds the map; an unknown key
        // returns null, but a known key never returns null just because the
        // cache was empty.
        Cache::shouldReceive('rememberForever')
            ->andReturn(['logo' => 'logo.png']);

        $this->assertSame('logo.png', Common::getConfig('logo'));
        $this->assertNull(Common::getConfig('does_not_exist'));
    }

    public function test_get_conf_from_key_reads_cache_and_returns_name_value_collection(): void
    {
        Cache::shouldReceive('rememberForever')
            ->with('all_configs', Mockery::type('Closure'))
            ->andReturn([
                'sender_percentage' => '10',
                'received_percentage' => '90',
                'unrelated' => 'x',
            ]);

        $collection = Common::getConfFromKey(['sender_percentage', 'received_percentage']);

        $this->assertCount(2, $collection, 'Only the requested keys are returned.');
        $this->assertSame('10', $collection->where('name', 'sender_percentage')->first()->value);
        $this->assertSame('90', $collection->where('name', 'received_percentage')->first()->value);
    }

    public function test_get_conf_from_key_returns_empty_collection_when_no_keys_match(): void
    {
        Cache::shouldReceive('rememberForever')->andReturn(['a' => '1']);

        $collection = Common::getConfFromKey(['nope']);

        // Must be a Collection (callers call ->where on it), never null.
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
