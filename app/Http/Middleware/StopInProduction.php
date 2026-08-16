<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopInProduction
{

    public function handle(Request $request, \Closure $next, ...$guards)
    {
        if (config('app.env') == 'production' && $this->isBlock()) abort(403, __('something went wrong'));

        return $next($request);
    }

    /**
     * Check if the current route should be blocked in production
     */
    public function isBlock(): bool
    {
        $blockProductionRoutes = [
            // Laravel Admin Helper Routes
            'helpers/routes',
            'helpers/terminal/database',
            'helpers/terminal/artisan',
            'helpers/scaffold',

            // Test & Debug Routes
            'test-pusher-config',
            'firebase-config',
            'test-fcm',
            'test-games',
            'test-branch',
            'test-push',
            'public-test',
            'public-official-test',
            'send_test_notifications',
            'test-game-rtm',

            // Debug Routes
            'debug/force-pusher',
            'debug/test-gift',
            'debug/test-user',
            'debug-request',
            '__debugbar',

            // Deployment & System Routes
            'deploy-webhook',
            'quick-reload',

            // Cache Control Routes
            'clear-admin-error',
        ];

        foreach ($blockProductionRoutes as $route) {
            if (str_contains(request()->url(), $route)) {
                \Log::warning('Blocked production route access attempt', [
                    'route' => $route,
                    'url' => request()->url(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'time' => now(),
                ]);
                return true;
            }
        }

        return false;
    }

}
