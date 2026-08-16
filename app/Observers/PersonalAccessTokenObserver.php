<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Keeps the latest_token_id_{userId} cache in sync with the source of truth
 * (the personal_access_tokens table), so CheckLatestToken can enforce the
 * single-device rule from cache without a per-request DB query.
 *
 * Token ids are auto-increment, so a newly created token is always the latest.
 * On single-model delete we invalidate the key; CheckLatestToken self-heals on
 * a cache miss by reading the current max once and repopulating.
 *
 * Note: mass deletes via $user->tokens()->delete() bypass Eloquent events; that
 * case is covered by CheckLatestToken's cache-miss fallback and by the next
 * createToken refreshing the key on re-login.
 */
class PersonalAccessTokenObserver
{
    private const TTL_SECONDS = 86400;

    public function created(PersonalAccessToken $token): void
    {
        if ($token->tokenable_id === null) {
            return;
        }

        Cache::put(
            $this->cacheKey($token->tokenable_id),
            $token->id,
            self::TTL_SECONDS
        );
    }

    public function deleted(PersonalAccessToken $token): void
    {
        if ($token->tokenable_id === null) {
            return;
        }

        Cache::forget($this->cacheKey($token->tokenable_id));
    }

    private function cacheKey($userId): string
    {
        return 'latest_token_id_' . $userId;
    }
}
