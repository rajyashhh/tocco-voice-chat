<?php

namespace App\Http\Middleware;

use App\Jobs\TrackUserIpJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IpMiddleware
{
    /**
     * How long (seconds) to suppress duplicate ip-tracking dispatches for the
     * same (ip,uid) pair. The feature only needs the *latest* ip, so re-writing
     * the same row on every request within the window is pure waste.
     */
    private const TRACK_WINDOW_SECONDS = 300;

    /**
     * Handle an incoming request.
     *
     * PERFORMANCE FIX: the synchronous updateOrCreate on every request is
     * replaced by an async TrackUserIpJob guarded by an atomic Cache::add (NX)
     * gate, mirroring the UpdateLastSeen pattern. This removes a DB write from
     * the hot read path while keeping the "record last ip per user" semantics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $ip = $request->ip();
        $uid = $user->id ?? null;

        $gateKey = 'ip_track_' . $ip . '_' . ($uid ?? 'guest');

        // Cache::add is atomic (SET ... NX): only the first request in the
        // window passes, so the job is dispatched at most once per (ip,uid)/window.
        if (Cache::add($gateKey, true, self::TRACK_WINDOW_SECONDS)) {
            TrackUserIpJob::dispatch($ip, $uid)->onQueue('default');
        }

        return $next($request);
    }
}
