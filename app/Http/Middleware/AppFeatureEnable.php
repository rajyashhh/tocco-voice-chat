<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Services\AppFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppFeatureEnable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$slugs): Response
    {
        foreach ($slugs as $slug) {
            if (!AppFeatureService::isEnable($slug)) {
                if ($request->is('api/*')) {
                    return Common::apiResponse(false, __('This feature has not been activated for you'), null, 403);
                }

                abort(403, __('This feature has not been activated for you'));
            }
        }

        return $next($request);
    }
}
