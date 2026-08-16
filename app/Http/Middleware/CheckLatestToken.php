<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckLatestToken
{
    private const TTL_SECONDS = 86400;

    /**
     * Enforce single-device login without a per-request DB query.
     *
     * PERFORMANCE FIX: the previous implementation ran an extra query on
     * personal_access_tokens for every authenticated request to fetch the
     * latest token. Sanctum already resolves the token used to authenticate as
     * $user->currentAccessToken() (in memory, zero queries). The single-device
     * rule is simply: the token used to authenticate must be the newest one.
     * We compare currentAccessToken()->id against a cached latest_token_id,
     * kept in sync by PersonalAccessTokenObserver (createToken / delete). On a
     * cache miss we read the current max once (DB = source of truth) and cache
     * it — not a fallback that hides failure, but a self-healing cache.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentToken = $user->currentAccessToken();

            // No resolvable token on the authenticated user => not authenticated.
            if (!$currentToken || $currentToken->getKey() === null) {
                return Common::apiResponse(false, 'Unauthenticated', [], 401);
            }

            $latestTokenId = $this->latestTokenId($user);

            if ($latestTokenId === null) {
                return Common::apiResponse(false, 'Unauthenticated', [], 401);
            }

            if ((int) $currentToken->getKey() !== (int) $latestTokenId) {
                return Common::apiResponse(0, 'Another device login with your account', null, 505);
            }
        }

        return $next($request);
    }

    /**
     * The id of the user's newest token, read from cache and rebuilt from the
     * DB (single point lookup) only on a cache miss.
     */
    private function latestTokenId($user): ?int
    {
        $cacheKey = 'latest_token_id_' . $user->id;

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        $latestId = $user->tokens()->max('id');
        if ($latestId === null) {
            return null;
        }

        Cache::put($cacheKey, (int) $latestId, self::TTL_SECONDS);

        return (int) $latestId;
    }
}
