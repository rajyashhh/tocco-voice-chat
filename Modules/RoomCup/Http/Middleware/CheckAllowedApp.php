<?php

namespace  Modules\RoomCup\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;

class CheckAllowedApp
{

    public function handle(Request $request, Closure $next)
    {
        // Default = enabled: features ship ON (FeatureFlagsDefaultsSeeder rule),
        // and a missing settings row must not hide the admin pages behind a hard
        // 404 — mirrors HostLevelActionMiddleWare (default 1, feature_blocked 403).
        $getSetting = function ($key, $default = 1) {
            return \Cache::rememberForever($key, function () use ($key, $default) {
                return Setting::where('key', $key)->value('value') ?? $default;
            });
        };

        $roomCup = $getSetting('room_cup');
        $roomCupSetting = $getSetting('room_cup_setting');
        if (!$roomCup && !$roomCupSetting) {
            return response()->view('feature_blocked', [], 403);
        }

        return $next($request);
    }
}
