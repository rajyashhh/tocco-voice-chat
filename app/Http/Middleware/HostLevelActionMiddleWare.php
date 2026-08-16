<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HostLevelActionMiddleWare
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


        $utdHostLevel = $getSetting('host_level_action') ?? 0;
        // dd($utdHostLevel);
        if (!$utdHostLevel) {

            return response()->view('feature_blocked', [], 403);
        }

        return $next($request);
    }
}
