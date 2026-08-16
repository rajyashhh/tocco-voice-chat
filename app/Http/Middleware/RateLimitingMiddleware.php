<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Common;

class RateLimitingMiddleware
{
    public function handle($request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
            $data = Cache::get('cach-data-mystore-'.$request->user()->id);
            if ($data) {
                return $data;
            }

        RateLimiter::hit($key, $decaySeconds = 60);

        return $next($request);
    }

    protected function resolveRequestSignature($request)
    {
        return sha1(
            $request->method() .
            '|' . $request->route()->uri() .
            '|' . $request->getContent().
            '|' . $request->user()
        );
    }

}
