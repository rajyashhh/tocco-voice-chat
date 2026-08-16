<?php

namespace Modules\Vip\Http\Middleware;

use Closure;
use App\Services\AppFeatureService;

class CheckVipFeatureEnabled
{
    public function handle($request, Closure $next)
    {
        (new AppFeatureService)->validateStatusEnable("vips");
        return $next($request);
    }
}
