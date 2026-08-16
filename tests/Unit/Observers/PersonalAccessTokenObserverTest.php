<?php

namespace Tests\Unit\Observers;

use App\Observers\PersonalAccessTokenObserver;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Unit Test: App\Observers\PersonalAccessTokenObserver
 *
 * The observer is the single point that keeps latest_token_id_{userId} in sync
 * with token creation/deletion, so CheckLatestToken can enforce single-device
 * login from cache. These tests touch only the cache (no DB).
 */
class PersonalAccessTokenObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function token(int $id, ?int $tokenableId): PersonalAccessToken
    {
        $token = new PersonalAccessToken();
        $token->forceFill([
            'id' => $id,
            'tokenable_id' => $tokenableId,
        ]);

        return $token;
    }

    public function test_created_caches_new_token_as_latest(): void
    {
        (new PersonalAccessTokenObserver())->created($this->token(777, 42));

        $this->assertSame(777, Cache::get('latest_token_id_42'));
    }

    public function test_created_overwrites_previous_latest(): void
    {
        $observer = new PersonalAccessTokenObserver();

        $observer->created($this->token(100, 42));
        $observer->created($this->token(200, 42));

        // The newest auto-increment id wins (single-device: latest token only).
        $this->assertSame(200, Cache::get('latest_token_id_42'));
    }

    public function test_deleted_invalidates_cache(): void
    {
        Cache::put('latest_token_id_42', 999, 60);

        (new PersonalAccessTokenObserver())->deleted($this->token(999, 42));

        $this->assertNull(Cache::get('latest_token_id_42'));
    }

    public function test_null_tokenable_is_ignored(): void
    {
        Cache::put('latest_token_id_42', 5, 60);

        $observer = new PersonalAccessTokenObserver();
        $observer->created($this->token(123, null));
        $observer->deleted($this->token(123, null));

        // Untouched: a token with no owner must not corrupt any user's key.
        $this->assertSame(5, Cache::get('latest_token_id_42'));
    }
}
