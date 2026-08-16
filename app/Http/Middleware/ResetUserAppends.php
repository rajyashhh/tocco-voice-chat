<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User::$withoutAppends is a GLOBAL static flipped by the withoutAppends()
 * scope. Under Octane the worker process outlives the request, so a single
 * code path that enables it and forgets to reset poisons EVERY later request
 * served by that worker: all avatar/gender/… accessors return empty app-wide
 * (the 2026-06-11 "black logo images" epidemic — random per worker, sticky).
 *
 * Resetting at the very start of each request guarantees a clean slate no
 * matter what the previous request did.
 */
class ResetUserAppends
{
    public function handle(Request $request, Closure $next): Response
    {
        User::$withoutAppends = false;

        return $next($request);
    }
}
