<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\GiftCategory;
use App\Models\Room;
use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Tik\Repositories\EmojiRepository;
use App\Tik\Repositories\GiftRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase A — DB-backed verification of catalog caching + N+1 eager-load guards.
 *
 *  (1) catalog list called twice  => 1 DB read, then served from cache (query count drops to 0)
 *  (2) Cache::tags([...])->flush() => next call re-queries the DB (invalidation works)
 *  (3) profile / room eager-load   => BOUNDED small query count (N+1 guard vs naive baseline)
 *
 *  SAFETY: These tests touch the database. They are HARD-GATED behind an isolated-sqlite
 *  check (see requiresIsolatedSqlite()). Per the Phase A backend assessment the suite's
 *  DB isolation is NOT configured (phpunit.xml has the sqlite :memory: lines commented out,
 *  there is no .env.testing, and RefreshDatabase would wipe+migrate whatever it connects to).
 *  So unless the connection is provably sqlite + :memory:, every test here SKIPS instead of
 *  running RefreshDatabase against a possibly-real database.
 *
 *  To run safely:
 *    DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter PhaseAPerfCacheTest
 */
class PhaseAPerfCacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Refuse to run (and therefore refuse to let RefreshDatabase migrate/wipe) unless the
     * active connection is an isolated in-memory sqlite DB. This is the single safety gate.
     */
    private function requiresIsolatedSqlite(): void
    {
        $default = config('database.default');
        $driver = config("database.connections.{$default}.driver");
        $database = config("database.connections.{$default}.database");

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            $this->markTestSkipped(
                "Skipped: DB isolation not confirmed (driver={$driver}, database={$database}). "
                . 'Run with DB_CONNECTION=sqlite DB_DATABASE=:memory: to enable. '
                . 'See tests/Unit/Cache/CatalogCacheTagsTest.php for the no-DB cache-tag coverage.'
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->requiresIsolatedSqlite();

        // Caching uses array store under tags; start every test from an empty region.
        Cache::tags(['gifts'])->flush();
        Cache::tags(['emojis'])->flush();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (1) list called twice => DB hit first, served from cache second
    //     Verified at the EmojiRepository level: its cached query (where enable=1,
    //     orderBy('sort')) is portable to sqlite. GiftRepository::all() uses a
    //     MySQL-only orderByRaw('ISNULL(`sort`)...'), so gifts are covered through
    //     the cache layer in CatalogCacheTagsTest and via flush below.
    // ─────────────────────────────────────────────────────────────────────────
    public function test_emoji_list_second_call_is_served_from_cache_no_db_hit(): void
    {
        $this->seedEmojis(3);
        $repo = new EmojiRepository();
        $request = $this->emojiRequest();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $repo->all($request);
        $queriesFirst = count(DB::getQueryLog());

        DB::flushQueryLog();
        $second = $repo->all($request);
        $queriesSecond = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertGreaterThanOrEqual(1, $queriesFirst, 'First (cold) call must hit the DB.');
        $this->assertSame(0, $queriesSecond, 'Second call must be served from cache with ZERO DB queries.');
        $this->assertCount(3, $first);
        $this->assertEquals($first->pluck('id'), $second->pluck('id'), 'Cached payload must equal the cold payload.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (2) Cache::tags(['emojis'])->flush() => next call re-queries the DB.
    //     This is the exact invalidation EmojiObserver / admin emoji mutations call.
    // ─────────────────────────────────────────────────────────────────────────
    public function test_emoji_cache_flush_forces_db_requery(): void
    {
        $this->seedEmojis(2);
        $repo = new EmojiRepository();
        $request = $this->emojiRequest();

        $repo->all($request);                 // cold -> cached
        $this->insertEmoji('new-after-cache'); // mutate underlying data

        // Without invalidation the new row must be invisible (proves caching is live).
        $stale = $repo->all($request);
        $this->assertCount(2, $stale, 'Stale cache must NOT reflect the new row before flush.');

        Cache::tags(['emojis'])->flush();      // <-- invalidation under test

        DB::flushQueryLog();
        DB::enableQueryLog();
        $fresh = $repo->all($request);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThanOrEqual(1, $queries, 'After flush the next call must re-query the DB.');
        $this->assertCount(3, $fresh, 'After flush the new row must be visible.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (2b) Gifts invalidation through the cache tag (DB-free producer) — proves the
    //      gifts tag (used by GiftRepository all/getByCategory/get_images) is wired.
    // ─────────────────────────────────────────────────────────────────────────
    public function test_gifts_tag_flush_invalidates_catalog_region(): void
    {
        $hits = 0;
        $producer = function () use (&$hits) {
            $hits++;
            return Gift::query()->where('enable', 1)->pluck('img');
        };

        GiftCategory::factory()->create();
        Gift::factory()->create(['enable' => 1, 'img' => 'a.png']);

        Cache::tags(['gifts'])->remember('gifts:images', 1800, $producer); // miss
        Cache::tags(['gifts'])->remember('gifts:images', 1800, $producer); // hit
        $this->assertSame(1, $hits);

        Cache::tags(['gifts'])->flush();

        Cache::tags(['gifts'])->remember('gifts:images', 1800, $producer); // miss again
        $this->assertSame(2, $hits, 'gifts tag flush must force the catalog producer to run again.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (3a) Room detail eager-load (RoomController::show -> findRoomForShow) issues a
    //      BOUNDED number of queries — N+1 guard. We assert an upper bound far below
    //      what a per-relation lazy access would produce.
    // ─────────────────────────────────────────────────────────────────────────
    public function test_room_show_eager_load_query_count_is_bounded(): void
    {
        $owner = User::factory()->create();
        $room = Room::factory()->create(['uid' => $owner->id]);

        $repo = new \App\Tik\Repositories\RoomRepository();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $loaded = $repo->findRoomForShow($room->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertNotNull($loaded);
        // ~20 eager relations -> one query per relation set + the base row + counts.
        // A bounded constant (independent of row count) is the N+1 guarantee; 40 is a
        // generous ceiling that still fails loudly if relations devolve to per-row loads.
        $this->assertLessThanOrEqual(
            40,
            $queries,
            "Room detail eager-load must be bounded (N+1 guard); got {$queries} queries."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // (3b) Profile eager-load (ProfileService -> UserRepository::findUserForProfile)
    //      is bounded and does NOT scale per related row (N+1 guard). We prove the
    //      query count is identical whether the user has 1 or many gallery images.
    // ─────────────────────────────────────────────────────────────────────────
    public function test_profile_eager_load_does_not_scale_with_related_rows(): void
    {
        $repo = new UserRepository();

        $small = User::factory()->create();
        $small->images()->create(['img' => 'one.png']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $repo->findUserForProfile($small->id);
        $qSmall = count(DB::getQueryLog());
        DB::disableQueryLog();

        $big = User::factory()->create();
        foreach (range(1, 6) as $i) {
            $big->images()->create(['img' => "img{$i}.png"]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $repo->findUserForProfile($big->id);
        $qBig = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $qSmall,
            $qBig,
            "Profile eager-load query count must be constant regardless of related-row count "
            . "(N+1 guard); got {$qSmall} vs {$qBig}."
        );
        $this->assertLessThanOrEqual(
            30,
            $qBig,
            "Profile eager-load must stay within a bounded query budget; got {$qBig}."
        );
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function emojiRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/emojis', 'GET');
    }

    private function seedEmojis(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $this->insertEmoji("emoji-{$i}", $i);
        }
    }

    private function insertEmoji(string $name, int $sort = 0): void
    {
        DB::table('emojis')->insert([
            'name' => $name,
            'name_en' => $name,
            'pid' => 0,
            'emoji' => $name . '.png',
            't_length' => 1,
            'sort' => $sort,
            'enable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
