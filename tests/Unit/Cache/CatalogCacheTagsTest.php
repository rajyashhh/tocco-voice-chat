<?php

namespace Tests\Unit\Cache;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase A — Catalog cache-tag behavior (pure cache layer, NO database, NO Redis).
 *
 * This mirrors EXACTLY the caching contract used by:
 *   - App\Tik\Repositories\GiftRepository  (all / getByCategory / get_images)  => Cache::tags(['gifts'])
 *   - App\Tik\Repositories\EmojiRepository (all / index)                       => Cache::tags(['emojis'])
 *   - Invalidation: admin gift/category + emoji mutations => Cache::tags([...])->flush()
 *
 * It does not exercise the DB at all: it drives the cache facade directly with the
 * same keys/tags the repositories use, and asserts the remember -> hit -> flush -> miss
 * lifecycle. phpunit.xml sets CACHE_DRIVER=array, and Laravel 10's ArrayStore is a
 * TaggableStore, so tag operations are fully supported here without any external service.
 */
class CatalogCacheTagsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Guarantee a clean tagged region regardless of ambient driver.
        Cache::tags(['gifts'])->flush();
        Cache::tags(['emojis'])->flush();
    }

    /**
     * remember() under a tag computes once, then serves from cache (the producer
     * callback must NOT run a second time while the entry is live).
     */
    public function test_tagged_remember_computes_once_then_serves_from_cache(): void
    {
        $calls = 0;
        $key = 'gifts:list:type:6';

        $producer = function () use (&$calls) {
            $calls++;
            return ['id' => 1, 'name' => 'Rose'];
        };

        $first = Cache::tags(['gifts'])->remember($key, 1800, $producer);
        $second = Cache::tags(['gifts'])->remember($key, 1800, $producer);

        $this->assertSame(1, $calls, 'Producer should run exactly once; the 2nd call must be a cache hit.');
        $this->assertSame($first, $second, 'Cached value must be returned identically on the 2nd call.');
        $this->assertSame('Rose', $second['name']);
    }

    /**
     * Cache::tags(['gifts'])->flush() invalidates the gifts region, forcing the next
     * remember() to recompute (the invalidation path used by admin gift/category mutations).
     */
    public function test_gifts_tag_flush_forces_recompute(): void
    {
        $calls = 0;
        $key = 'gifts:category:all:type:all';

        $producer = function () use (&$calls) {
            $calls++;
            return $calls; // value changes each recompute so we can prove a miss happened
        };

        $v1 = Cache::tags(['gifts'])->remember($key, 1800, $producer); // miss -> 1
        Cache::tags(['gifts'])->remember($key, 1800, $producer);       // hit  -> 1
        $this->assertSame(1, $calls, 'Second read before flush must be a hit.');

        Cache::tags(['gifts'])->flush();                              // invalidate

        $v2 = Cache::tags(['gifts'])->remember($key, 1800, $producer); // miss -> 2
        $this->assertSame(2, $calls, 'After flush the producer must run again.');
        $this->assertNotSame($v1, $v2, 'A fresh value must be produced after invalidation.');
    }

    /** The emojis tag flushes independently of the gifts tag. */
    public function test_emojis_tag_flush_is_isolated_from_gifts_tag(): void
    {
        Cache::tags(['gifts'])->remember('gifts:images', 1800, fn () => 'gifts-value');
        Cache::tags(['emojis'])->remember('emojis:list:pid:all', 600, fn () => 'emojis-value');

        Cache::tags(['emojis'])->flush();

        $this->assertNull(
            Cache::tags(['emojis'])->get('emojis:list:pid:all'),
            'Flushing emojis must drop the emojis entry.'
        );
        $this->assertSame(
            'gifts-value',
            Cache::tags(['gifts'])->get('gifts:images'),
            'Flushing emojis must NOT affect the gifts region.'
        );
    }

    /** A tagged entry is not visible through the untagged cache namespace (key isolation). */
    public function test_tagged_entry_is_namespaced_away_from_untagged_reads(): void
    {
        Cache::tags(['gifts'])->put('gifts:images', 'tagged', 1800);

        $this->assertNull(
            Cache::get('gifts:images'),
            'Tagged writes must not leak into the global (untagged) namespace.'
        );
        $this->assertSame('tagged', Cache::tags(['gifts'])->get('gifts:images'));
    }
}
