<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Models\AppFeature;
use App\Services\AppFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAgencyFeatureEnable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$slug): Response
    {
        $app_feature = \Cache::get('host_agency');

        if (!($app_feature == '1' || $app_feature == 1)) {
            abort(403, __('This feature has not been activated for you'));
        }

        return $next($request);
    }
}
