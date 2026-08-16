<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RoomBoomMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $getSetting = function ($key, $default = 0) {
            return \Cache::rememberForever($key, function () use ($key, $default) {
                return Setting::where('key', $key)->value('value') ?? $default;
            });
        };
        $roomBoom = (bool) $getSetting('room_boom');
        $roomBoomEnable = (bool) $getSetting('enable_room_boom');

        if (!$roomBoom || !$roomBoomEnable) abort(403, __('Not Found'));

        return $next($request);
    }
}
