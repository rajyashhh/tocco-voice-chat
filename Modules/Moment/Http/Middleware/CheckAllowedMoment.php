<?php

namespace  Modules\Moment\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckAllowedMoment
{

    public function handle(Request $request, Closure $next)
    {
        $getSetting = function ($key, $default = 0) {
            return \Cache::rememberForever($key, function () use ($key, $default) {
                return Setting::where('key', $key)->value('value') ?? $default;
            });
        };

        $momentStatus = $getSetting('moment_status') ?? 0;
        // $momentStatusSetting = $getSetting('moment_status_setting') ?? 0;
        if (!$momentStatus) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الميزة غير مفعلة'
                ], 403);
            }

            abort(403, 'هذه الميزة غير مفعلة');
        }

        return $next($request);
    }
}
