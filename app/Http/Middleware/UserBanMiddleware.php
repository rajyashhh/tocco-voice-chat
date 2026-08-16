<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Services\BanGuard;
use Closure;
use Illuminate\Http\Request;

class UserBanMiddleware
{
    public function __construct(private BanGuard $banGuard)
    {
    }

    /**
     * Handle an incoming request.
     *
     * PERFORMANCE FIX: dropped the unused loadMissing('packs') and routed the
     * action-ban check through the cached BanGuard, which reuses the same cached
     * ban snapshot as GeneralBanMiddleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $message = $this->banGuard->actionBanMessage($user->uuid, $request);

        if ($message) {
            return Common::apiResponse(0, $message, null, 377);
        }

        return $next($request);
    }
}
