<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HostLevelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $getSetting = function ($key, $default = 1) {
            return \Cache::rememberForever($key, function () use ($key, $default) {
                return Setting::where('key', $key)->value('value') ?? $default;
            });
        };

        $hostLevel = $getSetting('host_level_enabled') ?? 1;
        $utdHostLevel = (int)$getSetting('host_level_action') ?? 0;
      
       
       if (!$hostLevel || !$utdHostLevel) return response()->json(['error' => 'something wrong'], 500);

        return $next($request);
    }
}
