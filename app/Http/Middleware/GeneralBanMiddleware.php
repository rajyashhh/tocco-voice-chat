<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Services\BanGuard;
use Closure;
use Illuminate\Http\Request;

class GeneralBanMiddleware
{
    public function __construct(private BanGuard $banGuard)
    {
    }

    /**
     * Handle an incoming request.
     *
     * PERFORMANCE FIX: dropped the unused loadMissing('packs') (the ban logic
     * never touches $user->packs) and routed the login-ban check through the
     * cached BanGuard so the common (not-banned) request hits no DB.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $message = $this->banGuard->loginBanMessage($user->uuid, $request);

        if ($message) {
            return Common::apiResponse(0, $message, null, 501);
        }

        return $next($request);
    }
}
