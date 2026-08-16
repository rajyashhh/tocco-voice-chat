<?php

namespace Modules\SalaryTransaction\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAgencyFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $app_feature = \Cache::get('host_agency');
        if (!$app_feature) {
            throw new \Exception(__('Agency Feature is Disabled, Contact the administration'));
        }
        return $next($request);
    }
}
