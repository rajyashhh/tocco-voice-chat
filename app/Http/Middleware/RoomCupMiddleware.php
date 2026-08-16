<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RoomCupMiddleware
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

        $roomCup = $getSetting('room_cup') ?? 0;
        $roomCupSetting = $getSetting('room_cup_setting') ?? 0;
        if (!$roomCup && !$roomCupSetting) return response()->json(['error' => 'something wrong'], 500);

        return $next($request);
    }
}
