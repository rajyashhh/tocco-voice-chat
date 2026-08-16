<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckCpu
{
    /**
     * Overload safety valve for the lucky-gift hot path.
     *
     * Old behaviour rejected requests at a hard-coded 1-minute load average of
     * 11.5 (~1.4x an 8-core box). Because load average also counts tasks parked
     * on I/O round-trips, it crossed 11.5 while the CPU was still ~85% idle, so
     * the gate shed traffic the server could easily have served.
     *
     * New behaviour scales the threshold with the actual core count and only
     * trips on genuine sustained overload (load >> cores). Cheap: no DB/cache
     * round-trip is added to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'local' && Str::contains($request->url(), 'lucky-gift')) {
            $cores = function_exists('swoole_cpu_num') ? swoole_cpu_num() : 8;
            $threshold = $cores * 8; // e.g. 64 on an 8-core box

            $load = sys_getloadavg()[0] ?? 0;
            if ($load > $threshold) {
                return response(__('api.try_again'), 400);
            }
        }

        return $next($request);
    }
}
