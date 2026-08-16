<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '30', string $decayMinutes = '1'): Response
    {
        $ip = $request->ip();

        $allowedIps = config('admin.allowed_ips');
        if (is_array($allowedIps) && count($allowedIps) > 0 && !in_array($ip, $allowedIps, true)) {
            Log::warning('Admin access blocked by IP allowlist', [
                'ip' => $ip,
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403);
        }

        $key = 'admin_access:' . $ip;

        try {
            $attempts = (int) Cache::get($key, 0);

            if ($attempts >= (int) $maxAttempts) {
                Log::warning('Admin access rate limit exceeded', [
                    'ip' => $ip,
                    'path' => $request->path(),
                    'user_agent' => $request->userAgent(),
                    'attempts' => $attempts,
                ]);

                abort(429);
            }

            Cache::put($key, $attempts + 1, now()->addMinutes((int) $decayMinutes));
        } catch (\Throwable $e) {
            Log::warning('Admin access control fail-open: cache unavailable', [
                'ip' => $ip,
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
